<?php

namespace App\Services\Sarpras;

use App\Models\AssetPhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PhotoDocumentationService
{
    public function upload(Model $context, UploadedFile $file, array $meta = []): AssetPhoto
    {
        return DB::transaction(function () use ($context, $file, $meta) {
            $path = $file->store(
                $this->diskPath($context),
                'public'
            );

            $photo = AssetPhoto::create([
                'asset_id' => $this->resolveAssetId($context),
                'context_type' => $context->getMorphClass(),
                'context_id' => $context->getKey(),
                'photo_path' => $path,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'caption' => $meta['caption'] ?? null,
                'photo_type' => $meta['photo_type'] ?? 'documentation',
                'taken_at' => $meta['taken_at'] ?? now(),
                'uploaded_by' => $meta['uploaded_by'] ?? null,
                'metadata' => $meta['metadata'] ?? null,
            ]);

            // Emit a generic event so dashboards/asset passport pick it up.
            if (method_exists($context, 'asset') && $context->asset) {
                app(AssetEventLogger::class)->log($context->asset, 'photo_uploaded', [
                    'context_type' => $context->getMorphClass(),
                    'context_id' => $context->getKey(),
                    'photo_id' => $photo->id,
                    'photo_type' => $photo->photo_type,
                ], $photo->uploaded_by);
            }

            return $photo;
        });
    }

    public function uploadMany(Model $context, array $files, array $meta = []): array
    {
        $uploaded = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploaded[] = $this->upload($context, $file, $meta);
            }
        }

        return $uploaded;
    }

    public function delete(AssetPhoto $photo): bool
    {
        try {
            Storage::disk('public')->delete($photo->file_path);
        } catch (\Throwable $e) {
            Log::warning("Could not delete file {$photo->file_path}: ".$e->getMessage());
        }

        return $photo->delete();
    }

    public function forContext(Model $context)
    {
        return AssetPhoto::where('context_type', $context->getMorphClass())
            ->where('context_id', $context->getKey())
            ->orderByDesc('taken_at')
            ->get();
    }

    public function forAsset(string $assetId)
    {
        return AssetPhoto::where('asset_id', $assetId)
            ->orderByDesc('taken_at')
            ->get();
    }

    protected function resolveAssetId(Model $context): ?string
    {
        if ($context instanceof \App\Models\Asset) {
            return $context->getKey();
        }
        if (isset($context->asset_id)) {
            return $context->asset_id;
        }
        if (method_exists($context, 'asset') && $context->asset) {
            return $context->asset->getKey();
        }

        return null;
    }

    protected function diskPath(Model $context): string
    {
        $type = strtolower(class_basename($context));

        return "sarpras/photos/{$type}/".now()->format('Y/m');
    }
}
