<?php

namespace App\Services\Dormitory;

use App\Models\DormitoryQrScanLog;
use Illuminate\Http\Request;

class DormitoryQrAudit
{
    public function record(
        Request $request,
        string $dormitoryId,
        string $action,
        string $result,
        ?string $permitId = null,
        ?string $visitId = null,
        ?string $reason = null,
    ): void {
        try {
            DormitoryQrScanLog::create([
                'dormitory_id' => $dormitoryId,
                'permit_id' => $permitId,
                'visit_id' => $visitId,
                'user_id' => $request->user()?->id,
                'action' => $action,
                'result' => $result,
                'reason' => $reason,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Gagal mencatat audit QR asrama: '.$e->getMessage());
        }
    }
}
