<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AuditDiscrepancy;
use App\Models\AuditSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SarprasCacheInvalidator;

class AuditorWorkspaceService
{
    public function __construct(
        protected PhotoDocumentationService $photoService,
        protected ChecklistEngine $checklistEngine,
        protected AssetEventLogger $eventLogger,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function startSession(User $auditor, array $payload): AuditSession
    {
        return DB::transaction(function () use ($auditor, $payload) {
            return AuditSession::create([
                'id' => (string) Str::uuid(),
                'session_code' => $this->generateSessionCode(),
                'auditor_id' => $auditor->id,
                'audit_type' => $payload['audit_type'] ?? 'periodic',
                'scope' => $payload['scope'] ?? [],
                'target_room_id' => $payload['target_room_id'] ?? null,
                'target_category_id' => $payload['target_category_id'] ?? null,
                'started_at' => now(),
                'status' => 'in_progress',
                'metadata' => $payload['metadata'] ?? null,
            ]);
        });
    }

    public function scanAsset(AuditSession $session, string $assetCode, User $auditor, array $payload = []): array
    {
        $asset = Asset::where('asset_code', $assetCode)
            ->orWhere('qr_code', $assetCode)
            ->orWhere('id', $assetCode)
            ->first();

        if (! $asset) {
            return [
                'found' => false,
                'message' => "Asset with code {$assetCode} not found.",
            ];
        }

        $existingAudit = AssetAudit::where('audit_session_id', $session->id)
            ->where('asset_id', $asset->id)
            ->first();

        if ($existingAudit) {
            return [
                'found' => true,
                'already_audited' => true,
                'asset' => $asset,
                'audit' => $existingAudit,
            ];
        }

        $audit = AssetAudit::create([
            'id' => (string) Str::uuid(),
            'audit_session_id' => $session->id,
            'asset_id' => $asset->id,
            'auditor_id' => $auditor->id,
            'physical_condition' => $payload['physical_condition'] ?? null,
            'location_verified' => $payload['location_verified'] ?? $asset->room_id,
            'expected_location' => $asset->room_id,
            'has_discrepancy' => false,
            'scanned_at' => now(),
        ]);

        if (! empty($payload['photos'])) {
            $this->photoService->uploadMany($session, $payload['photos'], [
                'photo_type' => 'audit',
                'uploaded_by' => $auditor->id,
                'metadata' => ['audit_session_id' => $session->id],
            ]);
        }

        return [
            'found' => true,
            'already_audited' => false,
            'asset' => $asset,
            'audit' => $audit,
        ];
    }

    public function reportDiscrepancy(AuditSession $session, AssetAudit $audit, User $auditor, array $payload): AuditDiscrepancy
    {
        return DB::transaction(function () use ($session, $audit, $auditor, $payload) {
            $discrepancy = AuditDiscrepancy::create([
                'id' => (string) Str::uuid(),
                'audit_session_id' => $session->id,
                'asset_audit_id' => $audit->id,
                'asset_id' => $audit->asset_id,
                'auditor_id' => $auditor->id,
                'discrepancy_type' => $payload['discrepancy_type'],
                'severity' => $payload['severity'] ?? 'medium',
                'description' => $payload['description'] ?? null,
                'expected_value' => $payload['expected_value'] ?? null,
                'actual_value' => $payload['actual_value'] ?? null,
                'resolution_status' => 'open',
            ]);

            $audit->update(['has_discrepancy' => true]);

            if (! empty($payload['photos'])) {
                $this->photoService->uploadMany($discrepancy, $payload['photos'], [
                    'photo_type' => 'damage',
                    'uploaded_by' => $auditor->id,
                ]);
            }

            return $discrepancy;
        });
    }

    public function completeSession(AuditSession $session, User $auditor, array $summary = []): AuditSession
    {
        return DB::transaction(function () use ($session, $auditor, $summary) {
            $assetsAudited = AssetAudit::where('audit_session_id', $session->id)->count();
            $discrepancies = AuditDiscrepancy::where('audit_session_id', $session->id)->count();

            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'assets_audited' => $assetsAudited,
                'discrepancies_found' => $discrepancies,
                'summary' => array_merge([
                    'auditor' => $auditor->name,
                    'duration_minutes' => $session->started_at ? now()->diffInMinutes($session->started_at) : null,
                ], $summary),
            ]);

            // Update the asset's last_audit_date for everything audited.
            AssetAudit::where('audit_session_id', $session->id)
                ->with('asset')
                ->get()
                ->each(function (AssetAudit $audit) {
                    if ($audit->asset) {
                        $audit->asset->update([
                            'last_audit_date' => $audit->scanned_at,
                            'condition' => $audit->physical_condition ?: $audit->asset->condition,
                        ]);
                        $this->eventLogger->logAudit($audit->asset, $audit->auditor_id);
                    }
                });

            $this->cacheInvalidator->invalidateAudit($session);

            return $session->fresh(['audits.asset', 'discrepancies']);
        });
    }

    public function sessionProgress(AuditSession $session): array
    {
        $audits = AssetAudit::where('audit_session_id', $session->id)->get();
        $discrepancies = AuditDiscrepancy::where('audit_session_id', $session->id)
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'session' => $session->only(['id', 'session_code', 'status', 'audit_type', 'started_at', 'completed_at']),
            'assets_scanned' => $audits->count(),
            'discrepancies_total' => array_sum($discrepancies),
            'discrepancies_by_severity' => $discrepancies,
            'discrepancies_open' => AuditDiscrepancy::where('audit_session_id', $session->id)
                ->where('resolution_status', 'open')
                ->count(),
        ];
    }

    protected function generateSessionCode(): string
    {
        $year = now()->year;
        $seq = AuditSession::whereYear('created_at', $year)->count() + 1;
        return sprintf('AUD-%d-%04d', $year, $seq);
    }
}