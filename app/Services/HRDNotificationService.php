<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationUniversal;
use App\Models\CutiRequest;
use App\Models\KontrakKerja;
use App\Models\Pelatihan;
use App\Models\PelatihanPeserta;
use App\Models\KesejahteraanKlaim;
use App\Models\GtkProfile;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HRDNotificationService
{
    public const MODULE_CUTI       = 'hrd.cuti';
    public const MODULE_KONTRAK    = 'hrd.kontrak';
    public const MODULE_PELATIHAN  = 'hrd.pelatihan';
    public const MODULE_KINERJA    = 'hrd.kinerja';
    public const MODULE_KESEJAHTERAAN = 'hrd.kesejahteraan';

    public const TYPE_APPROVAL     = 'approval';
    public const TYPE_REMINDER     = 'reminder';
    public const TYPE_STATUS       = 'status_change';
    public const TYPE_INFO         = 'info';
    public const TYPE_NEW_REQUEST  = 'new_request';

    public static function personaliaAdmins()
    {
        return User::whereHas('roles', fn($q) => $q->whereIn('name', ['Personalia', 'Super Admin', 'Admin Tata Usaha']))->get();
    }

    public function notifyCutiRequest(CutiRequest $cuti): void
    {
        foreach (self::personaliaAdmins() as $admin) {
            $this->send(
                $admin->id,
                self::MODULE_CUTI,
                CutiRequest::class,
                $cuti->id,
                $cuti->id,
                self::TYPE_NEW_REQUEST,
                'Pengajuan Cuti Baru',
                ($cuti->user?->name ?? 'GTK') . ' mengajukan ' . ($cuti->template?->nama ?? 'cuti') . ' (' . $cuti->jumlah_hari . ' hari)',
                'cuti.index',
                $cuti->user_id,
                'normal'
            );
        }
    }

    public function notifyCutiDecision(CutiRequest $cuti, string $decision): void
    {
        $this->send(
            $cuti->user_id,
            self::MODULE_CUTI,
            CutiRequest::class,
            $cuti->id,
            $cuti->id,
            self::TYPE_STATUS,
            'Status Cuti: ' . ucfirst($decision),
            'Pengajuan ' . ($cuti->template?->nama ?? 'cuti') . ' Anda telah ' . ($decision === 'approved' ? 'disetujui' : 'ditolak'),
            'cuti.index',
            null,
            $decision === 'rejected' ? 'high' : 'normal'
        );
    }

    public function notifyKontrakExpiring(int $daysThreshold = 30): int
    {
        $expiring = KontrakKerja::with('gtk')
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_selesai', '>=', now())
            ->whereDate('tanggal_selesai', '<=', now()->addDays($daysThreshold))
            ->get();

        $count = 0;
        foreach ($expiring as $k) {
            foreach (self::personaliaAdmins() as $admin) {
                $this->send(
                    $admin->id,
                    self::MODULE_KONTRAK,
                    KontrakKerja::class,
                    $k->id,
                    $k->id,
                    self::TYPE_REMINDER,
                    'Kontrak Akan Berakhir',
                    'Kontrak ' . ($k->gtk?->nama ?? 'GTK') . ' berakhir pada ' . $k->tanggal_selesai->format('d M Y'),
                    'kontrak.expiring',
                    null,
                    'high'
                );
                $count++;
            }
        }
        return $count;
    }

    public function notifyPelatihanJadwal(Pelatihan $pelatihan): void
    {
        $peserta = PelatihanPeserta::where('pelatihan_id', $pelatihan->id)->pluck('user_id');
        foreach ($peserta as $uid) {
            $this->send(
                $uid,
                self::MODULE_PELATIHAN,
                Pelatihan::class,
                $pelatihan->id,
                $pelatihan->id,
                self::TYPE_INFO,
                'Jadwal Pelatihan',
                'Anda terdaftar pada pelatihan "' . $pelatihan->nama . '" mulai ' . $pelatihan->tanggal_mulai->format('d M Y'),
                'pelatihan.index',
                null,
                'normal'
            );
        }
    }

    public function notifyKesejahteraanKlaimApproved(KesejahteraanKlaim $klaim): void
    {
        $this->send(
            $klaim->user_id,
            self::MODULE_KESEJAHTERAAN,
            KesejahteraanKlaim::class,
            $klaim->id,
            $klaim->id,
            self::TYPE_STATUS,
            'Klaim Disetujui',
            'Klaim ' . ($klaim->kesejahteraan?->nama ?? 'kesejahteraan') . ' Anda telah disetujui',
            'kesejahteraan.index',
            null,
            'normal'
        );
    }

    protected function send(
        string $userId,
        string $module,
        string $refType,
        string $refId,
        string $refCode,
        string $type,
        string $title,
        string $message,
        string $routeName,
        ?string $createdBy,
        string $priority = 'normal'
    ): void {
        try {
            NotificationUniversal::create([
                'user_id'         => $userId,
                'module'          => $module,
                'reference_type'  => $refType,
                'reference_id'    => $refId,
                'reference_code'  => $refCode,
                'type'            => $type,
                'title'           => $title,
                'message'         => $message,
                'action_url'      => $routeName,
                'action_text'     => 'Lihat Detail',
                'priority'        => $priority,
                'data'            => ['route' => $routeName, 'by' => $createdBy],
            ]);
        } catch (\Throwable $e) {
            // swallow - notification should never break business flow
        }
    }

    public function unreadCountForUser(string $userId): int
    {
        return NotificationUniversal::where('user_id', $userId)
            ->where('is_read', false)
            ->whereIn('module', [
                self::MODULE_CUTI,
                self::MODULE_KONTRAK,
                self::MODULE_PELATIHAN,
                self::MODULE_KINERJA,
                self::MODULE_KESEJAHTERAAN,
            ])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    public function pendingApprovalCount(): int
    {
        return CutiRequest::where('status', CutiRequest::STATUS_PENDING)->count();
    }
}
