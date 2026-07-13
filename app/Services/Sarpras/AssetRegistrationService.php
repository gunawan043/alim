<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetPhoto;
use Illuminate\Support\Facades\DB;

class AssetRegistrationService
{
    public function __construct(private AssetEventLogger $logger) {}

    /**
     * Register a new asset with full lifecycle setup.
     * Returns the created asset model.
     */
    public function register(array $data, ?int $actorId = null): Asset
    {
        return DB::transaction(function () use ($data, $actorId) {
            if (empty($data['asset_code'])) {
                $data['asset_code'] = $this->generateAssetCode();
            }

            if (empty($data['created_by'])) {
                $data['created_by'] = $actorId ?? auth()->id();
            }

            $asset = Asset::create($data);

            $this->logger->logCreated($asset, $actorId);

            return $asset;
        });
    }

    /**
     * Move asset to another room and emit lifecycle event.
     */
    public function moveToRoom(Asset $asset, string $toRoomId, ?int $actorId = null, ?string $notes = null): Asset
    {
        return DB::transaction(function () use ($asset, $toRoomId, $actorId) {
            $fromRoom = $asset->room;

            $asset->update(['room_id' => $toRoomId]);
            $asset->refresh()->load('room');

            $this->logger->logMoved(
                $asset,
                $fromRoom?->room_name,
                $asset->room?->room_name,
                $actorId
            );

            return $asset;
        });
    }

    public function recordPhoto(Asset $asset, string $path, ?int $uploaderId = null): AssetPhoto
    {
        return DB::transaction(function () use ($asset, $path, $uploaderId) {
            $photo = AssetPhoto::create([
                'asset_id' => $asset->id,
                'photo_path' => $path,
                'uploaded_by' => $uploaderId ?? auth()->id(),
            ]);

            $this->logger->log($asset, 'photo_uploaded', [
                'photo_id' => $photo->id,
                'uploaded_by' => $uploaderId ?? auth()->id(),
            ], $uploaderId ?? auth()->id());

            return $photo;
        });
    }

    public function recordAudit(Asset $asset, array $auditData, ?int $actorId = null): Asset
    {
        return DB::transaction(function () use ($asset, $auditData, $actorId) {
            $asset->update([
                'last_audit_date' => now(),
                'last_audit_by' => $actorId ?? auth()->id(),
                'condition' => $auditData['condition'] ?? $asset->condition,
                'last_condition_update' => now(),
            ]);

            $this->logger->logAudit($asset, $actorId ?? auth()->id());

            return $asset;
        });
    }

    protected function generateAssetCode(): string
    {
        $year = now()->year;
        $count = Asset::whereYear('created_at', $year)->count() + 1;

        return sprintf('AST-%s-%05d', $year, $count);
    }
}
