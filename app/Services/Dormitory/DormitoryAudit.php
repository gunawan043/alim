<?php

namespace App\Services\Dormitory;

use App\Models\AuditLog;

final class DormitoryAudit
{
    public static function log(string $action, string $table, ?string $recordId = null): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable) {
            // Audit tidak boleh memblokir aksi utama.
        }
    }
}
