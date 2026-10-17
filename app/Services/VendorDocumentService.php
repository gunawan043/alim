<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VendorDocumentService
{
    private const DISK = 'public';

    private const FOLDER = 'vendor-documents';

    public function __construct() {}

    public function upload(
        Vendor $vendor,
        UploadedFile $file,
        string $type,
        ?array $metadata = []
    ): VendorDocument {
        $path = $file->store(
            self::FOLDER.'/'.$vendor->id,
            self::DISK
        );

        $document = $vendor->documents()->create([
            'name' => $metadata['name'] ?? $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'type' => $type,
            'status' => VendorDocument::STATUS_PENDING,
            'expiry_date' => $metadata['expiry_date'] ?? null,
            'issued_date' => $metadata['issued_date'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'user_id' => $metadata['user_id'] ?? null,
        ]);

        $this->audit('upload', $document);

        return $document;
    }

    public function uploadNewVersion(
        VendorDocument $document,
        UploadedFile $file
    ): VendorDocument {
        // Archive old version
        $oldPath = $document->storage_path;
        $document->update([
            'storage_path' => $file->store(
                self::FOLDER.'/'.$document->vendor_id,
                self::DISK
            ),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => VendorDocument::STATUS_PENDING,
        ]);

        $this->audit('version_update', $document, ['old_path' => $oldPath]);

        return $document;
    }

    public function verify(VendorDocument $document, int $userId): VendorDocument
    {
        $document->update([
            'status' => VendorDocument::STATUS_VERIFIED,
            'verified_by' => $userId,
            'verified_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $this->audit('verify', $document);

        return $document;
    }

    public function reject(VendorDocument $document, int $userId, string $reason): VendorDocument
    {
        $document->update([
            'status' => VendorDocument::STATUS_REJECTED,
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->audit('reject', $document, ['reason' => $reason]);

        return $document;
    }

    public function checkExpiry(): int
    {
        $count = 0;

        VendorDocument::whereIn('status', ['verified', 'pending'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now())
            ->get()
            ->each(function ($doc) use (&$count) {
                $doc->update(['status' => VendorDocument::STATUS_EXPIRED]);
                $this->audit('expire', $doc);
                $count++;
            });

        return $count;
    }

    public function getExpiringSoon(int $days = 30): array
    {
        return VendorDocument::where('status', 'verified')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date')
            ->get()
            ->all();
    }

    public function delete(VendorDocument $document): void
    {
        if (Storage::disk(self::DISK)->exists($document->storage_path)) {
            Storage::disk(self::DISK)->delete($document->storage_path);
        }
        $this->audit('delete', $document);
        $document->delete();
    }

    public function download(VendorDocument $document)
    {
        $this->audit('download', $document);

        return Storage::disk(self::DISK)->download($document->storage_path);
    }

    private function audit(string $action, VendorDocument $document, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => $document->user_id ?? auth()->id(),
            'action' => "vendor_document.{$action}",
            'entity_type' => VendorDocument::class,
            'entity_id' => $document->id,
            'metadata' => array_merge([
                'vendor_id' => $document->vendor_id,
                'type' => $document->type,
            ], $meta),
        ]);
    }
}
