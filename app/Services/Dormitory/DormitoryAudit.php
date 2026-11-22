<?php

namespace App\Services\Dormitory;

use App\Models\AuditLog;

final class DormitoryAudit
{
    public static function log(
        string $action,
        string $table,
        ?string $recordId = null,
        array $metadata = [],
        bool $required = false
    ): void {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
        } catch (\Throwable $e) {
            // Default: audit tidak boleh memblokir aksi utama. Untuk command
            // persetujuan, audit bersifat wajib (required=true) agar keputusan
            // dan jejak audit tidak terpisah.
            if ($required) {
                throw $e;
            }
        }
    }
}
