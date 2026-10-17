<?php

namespace App\Observers;

use App\Models\InstitutionDecree;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi individu saat SK (Surat Keputusan) dibuat/diperbarui —
 * dikirim ke guru yang memiliki penugasan aktif pada SK tersebut.
 */
class InstitutionDecreeObserver
{
    public function created(InstitutionDecree $decree): void
    {
        $this->notifyAffected($decree, 'created');
    }

    public function updated(InstitutionDecree $decree): void
    {
        if ($decree->wasChanged(['status', 'title', 'decree_number', 'issued_date'])) {
            $this->notifyAffected($decree, 'updated');
        }
    }

    private function notifyAffected(InstitutionDecree $decree, string $event): void
    {
        try {
            $teacherIds = $decree->teachingAssignments()
                ->where('status', 'active')
                ->pluck('teacher_id')
                ->filter()
                ->unique();

            foreach ($teacherIds as $teacherId) {
                if (auth()->id() === $teacherId) {
                    continue;
                }

                app(NotificationUniversalService::class)->send($teacherId, [
                    'module' => 'kurikulum',
                    'reference_type' => InstitutionDecree::class,
                    'reference_id' => $decree->id,
                    'type' => 'info',
                    'action' => 'decree_'.$event,
                    'priority' => 'high',
                    'title' => $event === 'created' ? 'SK Baru Diterbitkan' : 'SK Diperbarui',
                    'message' => 'SK '.$decree->decree_number.' — '.$decree->title.' telah '.($event === 'created' ? 'diterbitkan' : 'diperbarui').'.',
                    'action_url' => route('user.institution-decrees.show', ['userId' => $teacherId, 'id' => $decree->id]),
                    'action_text' => 'Lihat SK',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Notif SK gagal: '.$e->getMessage());
        }
    }
}
