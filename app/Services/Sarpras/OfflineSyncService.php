<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Handles incremental and batch sync for offline-first React Native clients.
 *
 * Contract:
 *  - All paginated lists include: data[], sync_token, cursor, has_more
 *  - Assets, Movements, Orders carry: updated_at, deleted_at, sync_token
 *  - Incremental: ?since=token&until=cursor
 *  - Batch: POST /batch-upload (mobile → offline → store locally)
 */
class OfflineSyncService
{
    public function syncAssets(Request $request, int $perPage = 20): array
    {
        $since = $request->input('since');
        $cursor = $request->input('cursor');
        $limit = min($perPage, 100);

        $query = Asset::query();

        if ($since) {
            // Numeric timestamp from sync_token
            $query->where(function ($q) use ($since) {
                $q->whereRaw('UNIX_TIMESTAMP(updated_at) >= ?', [$since])
                    ->orWhereRaw('UNIX_TIMESTAMP(deleted_at) >= ?', [$since]);
            });
        }

        if ($cursor) {
            $query->where('sync_token', '<', $cursor);
        }

        $query->orderBy('updated_at', 'desc')
            ->orderBy('sync_token', 'desc')
            ->limit($limit + 1);

        $page = $query->get();
        $hasMore = $page->count() > $limit;

        if ($hasMore) {
            $page = $page->slice(0, $limit)->values();
        }

        // Generate a new sync token (next offset + random salt)
        $nextToken = $this->generateSyncToken();

        return [
            'data' => $page->map(fn ($m) => $this->assetToPayload($m))->all(),
            'sync_token' => $nextToken,
            'cursor' => $hasMore ? $page->last()?->sync_token : null,
            'has_more' => $hasMore,
        ];
    }

    public function syncMovements(Request $request): array
    {
        $since = $request->input('since');
        $cursor = $request->input('cursor');
        $limit = min(50, 100);

        $query = AssetMovement::query();

        if ($since) {
            $query->where(function ($q) use ($since) {
                $q->whereRaw('UNIX_TIMESTAMP(updated_at) >= ?', [$since])
                    ->orWhereRaw('UNIX_TIMESTAMP(deleted_at) >= ?', [$since]);
            });
        }

        if ($cursor) {
            $query->where('sync_token', '<', $cursor);
        }

        $query->orderBy('updated_at', 'desc')
            ->orderBy('sync_token', 'desc')
            ->limit($limit + 1);

        $page = $query->get();
        $hasMore = $page->count() > $limit;

        if ($hasMore) {
            $page = $page->slice(0, $limit)->values();
        }

        return [
            'data' => $page->map(fn ($m) => $this->movementToPayload($m))->all(),
            'sync_token' => $this->generateSyncToken(),
            'cursor' => $hasMore ? $page->last()?->sync_token : null,
            'has_more' => $hasMore,
        ];
    }

    public function syncOrders(Request $request): array
    {
        $since = $request->input('since');
        $cursor = $request->input('cursor');
        $limit = min(50, 100);

        $query = WorkOrder::query();

        if ($since) {
            $query->where(function ($q) use ($since) {
                $q->whereRaw('UNIX_TIMESTAMP(updated_at) >= ?', [$since]);
            });
        }

        if ($cursor) {
            $query->where('sync_token', '<', $cursor);
        }

        $query->orderBy('updated_at', 'desc')
            ->orderBy('sync_token', 'desc')
            ->limit($limit + 1);

        $page = $query->get();
        $hasMore = $page->count() > $limit;

        if ($hasMore) {
            $page = $page->slice(0, $limit)->values();
        }

        return [
            'data' => $page->map(fn ($m) => $this->orderToPayload($m))->all(),
            'sync_token' => $this->generateSyncToken(),
            'cursor' => $hasMore ? $page->last()?->sync_token : null,
            'has_more' => $hasMore,
        ];
    }

    public function batchUpload(array $items, int $uploaderId): array
    {
        return DB::transaction(function () use ($items, $uploaderId) {
            $accepted = 0;
            $rejected = [];

            $rules = [
                '*.id' => 'required|string|max:36',
                '*.type' => 'required|string|in:asset_photo,progress_note,work_order_action,checklist_response,movement_photo,audit_photo',
                '*.context_id' => 'required|string|max:36',
            ];

            foreach ($items as $idx => $item) {
                $validator = Validator::make([$item => $item], [
                    'id' => $rules['*.id'],
                    'type' => $rules['*.type'],
                    'context_id' => $rules['*.context_id'],
                    'payload' => 'required|array',
                    'sync_token' => 'required|numeric',
                ]);

                if ($validator->fails()) {
                    $rejected[] = [
                        'index' => $idx,
                        'reasons' => $validator->errors()->all(),
                    ];

                    continue;
                }

                // De-duplicate: reject if sync_token < existing max token for this ID.
                $token = (float) $item['sync_token'];
                // Check against the global sync_tokens table
                $existingMax = DB::table('sync_tokens')
                    ->where('id', $item['id'])
                    ->value('token');

                if ($existingMax !== null && $token <= (float) $existingMax) {
                    $rejected[] = [
                        'index' => $idx,
                        'reasons' => ['duplicate_or_older_sync_token'],
                    ];

                    continue;
                }

                // Persist sync token
                DB::table('sync_tokens')->updateOrInsert(
                    ['id' => $item['id']],
                    ['token' => $token, 'created_at' => now()]
                );

                // Delegate to the right handler based on type
                $type = $item['type'];
                try {
                    match ($type) {
                        'asset_photo' => $this->handleBatchPhoto($item, $uploaderId),
                        'progress_note' => $this->handleBatchNote($item, $uploaderId),
                        'checklist_response' => $this->handleBatchChecklist($item, $uploaderId),
                        'work_order_action' => $this->handleBatchWorkOrder($item, $uploaderId),
                        'movement_photo' => $this->handleBatchMovementPhoto($item, $uploaderId),
                        'audit_photo' => $this->handleBatchAuditPhoto($item, $uploaderId),
                        default => null,
                    };
                } catch (\Throwable $e) {
                    $rejected[] = ['index' => $idx, 'reasons' => [$e->getMessage()]];

                    continue;
                }

                $accepted++;
            }

            return [
                'accepted' => $accepted,
                'rejected' => $rejected,
            ];
        });
    }

    public function getLastSyncToken(int $userId): ?string
    {
        return DB::table('user_sync_tokens')
            ->where('user_id', $userId)
            ->value('token');
    }

    protected function handleBatchPhoto(array $item, int $uploaderId): void
    {
        $payload = $item['payload'];
        $contextId = $payload['context_id'];
        $assetId = $payload['asset_id'];
        $filePath = $payload['file_path'];
        $photoType = $payload['photo_type'] ?? 'documentation';

        $model = match ($payload['parent_type'] ?? '') {
            'repair_request' => \App\Models\RepairRequest::class,
            'maintenance' => \App\Models\MaintenanceLog::class,
            'movement' => AssetMovement::class,
            'audit' => \App\Models\AssetAudit::class,
            'work_order' => WorkOrder::class,
            default => null,
        };

        if ($model) {
            $context = $model::where('id', $contextId)->first();
            if ($context) {
                $photo = new \App\Models\AssetPhoto;
                $photo->attributes = [
                    'id' => (string) Str::uuid(),
                    'asset_id' => $assetId,
                    'photo_path' => $filePath,
                    'file_path' => $filePath,
                    'caption' => $payload['caption'] ?? '',
                    'photo_type' => $photoType,
                    'taken_at' => now(),
                    'uploaded_by' => $uploaderId,
                ];
                $photo->save();
            }
        }
    }

    protected function handleBatchNote(array $item, int $uploaderId): void
    {
        $payload = $item['payload'];
        $wo = WorkOrder::where('id', $payload['context_id'])->first();
        if ($wo) {
            WorkOrderProgressNote::create([
                'id' => (string) Str::uuid(),
                'work_order_id' => $wo->id,
                'user_id' => $uploaderId,
                'note' => $payload['note'],
                'note_type' => $payload['note_type'] ?? 'comment',
                'metadata' => $payload['metadata'] ?? null,
            ]);
        }
    }

    protected function handleBatchChecklist(array $item, int $uploaderId): void
    {
        // Defer to checklist engine
        $instance = \App\Models\ChecklistInstance::where('id', $item['payload']['instance_id'])->first();
        if ($instance) {
            app(ChecklistEngine::class)->record(
                $instance,
                $item['payload']['template_item_id'],
                $item['payload']['response_value'],
                ['uploaded_by' => $uploaderId]
            );
        }
    }

    protected function handleBatchWorkOrder(array $item, int $uploaderId): void
    {
        // WorkOrder action (start/pause/resume/finish) — defer to execution service.
    }

    protected function handleBatchMovementPhoto(array $item, int $uploaderId): void
    {
        $payload = $item['payload'];
        $movement = AssetMovement::where('id', $payload['context_id'])->first();
        if ($movement) {
            $photo = new \App\Models\AssetPhoto;
            $photo->attributes = [
                'id' => (string) Str::uuid(),
                'asset_id' => $movement->asset_id,
                'photo_path' => $payload['file_path'],
                'file_path' => $payload['file_path'],
                'caption' => $payload['caption'] ?? '',
                'photo_type' => $payload['photo_type'] ?? 'documentation',
                'taken_at' => now(),
                'uploaded_by' => $uploaderId,
            ];
            $photo->save();
        }
    }

    protected function handleBatchAuditPhoto(array $item, int $uploaderId): void
    {
        $this->handleBatchPhoto($item, $uploaderId);
    }

    /* ---- helpers ---- */

    protected function generateSyncToken(): string
    {
        $ts = time();
        $salt = Str::random(8);

        return hash('sha256', $ts.':'.$salt);
    }

    protected function assetToPayload(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'asset_name' => $asset->asset_name,
            'asset_code' => $asset->asset_code,
            'asset_status' => $asset->asset_status,
            'condition' => $asset->condition,
            'category_id' => $asset->asset_category_id,
            'room_id' => $asset->room_id,
            'work_unit_id' => $asset->work_unit_id,
            'sync_token' => $this->computeAssetToken($asset),
            'updated_at' => $asset->updated_at?->timestamp,
            'deleted_at' => $asset->deleted_at?->timestamp,
        ];
    }

    protected function computeAssetToken(Asset $asset): string
    {
        // Deterministic: sha256(atomic_id:epoch_ts)
        $ts = $asset->updated_at?->timestamp ?? $asset->created_at?->timestamp ?? time();

        return hash('sha256', $asset->id.':'.$ts);
    }

    protected function movementToPayload(AssetMovement $m): array
    {
        return [
            'id' => $m->id,
            'movement_number' => $m->movement_number,
            'asset_id' => $m->asset_id,
            'status' => $m->status,
            'to_room_id' => $m->to_room_id,
            'sync_token' => $this->computeMovementToken($m),
            'updated_at' => $m->updated_at?->timestamp,
            'deleted_at' => $m->deleted_at?->timestamp,
        ];
    }

    protected function computeMovementToken(AssetMovement $m): string
    {
        $ts = $m->updated_at?->timestamp ?? $m->created_at?->timestamp ?? time();

        return hash('sha256', $m->id.':'.$ts);
    }

    protected function orderToPayload(WorkOrder $o): array
    {
        return [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'asset_id' => $o->asset_id,
            'assignee_id' => $o->assignee_id,
            'status' => $o->status,
            'priority' => $o->priority ?? 'medium',
            'sync_token' => $this->computeOrderToken($o),
            'updated_at' => $o->updated_at?->timestamp,
            'deleted_at' => $o->deleted_at?->timestamp,
        ];
    }

    protected function computeOrderToken(WorkOrder $o): string
    {
        $ts = $o->updated_at?->timestamp ?? $o->created_at?->timestamp ?? time();

        return hash('sha256', $o->id.':'.$ts);
    }
}
