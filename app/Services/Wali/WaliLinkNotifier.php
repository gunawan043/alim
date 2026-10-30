<?php

namespace App\Services\Wali;

use App\Models\User;
use App\Models\WaliSantri;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi penghubungan wali ke Super Admin + seluruh jabatan Tata Usaha
 * (role Satuan Pendidikan, jabatan mengandung "Tata Usaha"). Tanpa role baru.
 */
class WaliLinkNotifier
{
    public function notifySchool(WaliSantri $link, string $event): int
    {
        try {
            $link->loadMissing(['user:id,name,email,no_hp', 'student:id,name,school_id', 'student.school:id,name']);

            $recipients = User::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('is_system_admin', true)
                        ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Super Admin'))
                        ->orWhere(function ($tu) {
                            $tu->whereHas('roles', fn ($roles) => $roles->where('name', 'Satuan Pendidikan'))
                                ->whereHas('employment', fn ($employment) => $employment->where('jabatan', 'like', '%tata usaha%'));
                        });
                })
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            if ($recipients === []) {
                Log::info('WaliLinkNotifier: tidak ada penerima Super Admin/TU.', [
                    'link_id' => $link->id,
                ]);

                return 0;
            }

            $isPending = $event === 'pending';
            $waliName = $link->user?->name ?? 'Wali';
            $studentName = $link->student?->name ?? 'Santri';
            $schoolName = $link->student?->school?->name ?? '';

            $title = $isPending
                ? 'Pengajuan Penghubungan Wali Menunggu Persetujuan'
                : 'Wali Baru Terhubung dengan Santri';

            $message = $isPending
                ? "{$waliName} mengajukan penghubungan sebagai wali dari {$studentName}".($schoolName ? " ({$schoolName})" : '').'. Menunggu persetujuan sekolah.'
                : "{$waliName} telah terhubung sebagai wali dari {$studentName}".($schoolName ? " ({$schoolName})" : '').'.';

            app(NotificationUniversalService::class)->sendToMany($recipients, [
                'module' => 'system',
                'reference_type' => WaliSantri::class,
                'reference_id' => $link->id,
                'reference_code' => (string) $link->role,
                'type' => 'info',
                'action' => $isPending ? 'wali_link_pending' : 'wali_link_active',
                'title' => $title,
                'message' => $message,
                'priority' => 'medium',
                'data' => [
                    'wali_id' => $link->user_id,
                    'student_id' => $link->student_id,
                    'relationship' => $link->role,
                    'status' => $link->status,
                ],
            ]);

            return count($recipients);
        } catch (\Throwable $e) {
            Log::warning('WaliLinkNotifier: gagal mengirim notifikasi.', [
                'link_id' => $link->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
