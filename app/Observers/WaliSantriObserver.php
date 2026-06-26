<?php

namespace App\Observers;

use App\Models\WaliSantri;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

class WaliSantriObserver
{
    public function __construct(
        private readonly NotificationUniversalService $notifier,
    ) {}

    /**
     * When a wali is verified/activated, notify both the wali (via their user)
     * and the student record. Without this, the activation event happens silently
     * and the wali has no idea they can now access the student's data.
     */
    public function updated(WaliSantri $wali): void
    {
        if (! $this->justBecameActive($wali)) {
            return;
        }

        $this->notifyWali($wali);
        $this->notifyAdmins($wali);
    }

    private function justBecameActive(WaliSantri $wali): bool
    {
        if ($wali->status !== WaliSantri::STATUS_ACTIVE) {
            return false;
        }

        $wasActive = $wali->getOriginal('status') === WaliSantri::STATUS_ACTIVE;

        return ! $wasActive && $wali->verified_at !== null
            && $wali->getOriginal('verified_at') === null;
    }

    private function notifyWali(WaliSantri $wali): void
    {
        $waliUser = $wali->user;
        if (! $waliUser) {
            return;
        }

        $student = $wali->student;
        $studentName = $student?->name ?? 'santri';

        try {
            $this->notifier->send(
                recipient: $waliUser,
                title: 'Akses Wali Santri Aktif',
                message: "Akses Anda sebagai wali {$wali->role} atas nama {$studentName} telah diverifikasi. Anda sekarang dapat mengakses data dan informasi perkembangan {$studentName}.",
                category: 'wali_santri',
                reference: $wali->id,
                referenceType: 'wali_santri',
                schoolId: $waliUser->school_id,
            );
        } catch (\Throwable $e) {
            Log::warning('WaliSantriObserver: gagal kirim notifikasi ke wali', [
                'wali_santri_id' => $wali->id,
                'wali_user_id' => $waliUser->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyAdmins(WaliSantri $wali): void
    {
        $student = $wali->student;
        $waliUser = $wali->user;
        if (! $student || ! $waliUser) {
            return;
        }

        try {
            $this->notifier->sendToSchoolAdmins(
                schoolId: $student->school_id,
                title: 'Wali Santri Baru Terverifikasi',
                message: "{$waliUser->name} ({$wali->role}) telah terverifikasi sebagai wali {$student->name}.",
                category: 'wali_santri',
                reference: $wali->id,
                referenceType: 'wali_santri',
            );
        } catch (\Throwable $e) {
            Log::warning('WaliSantriObserver: gagal kirim notifikasi ke admin', [
                'wali_santri_id' => $wali->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
