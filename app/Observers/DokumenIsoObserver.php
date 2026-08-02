<?php

namespace App\Observers;

use App\Mail\DokumenIsoMail;
use App\Models\DokumenIso;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DokumenIsoObserver
{
    /**
     * Field-field signifikan: perubahan di kolom ini memicu email notifikasi.
     */
    private const SIGNIFICANT_FIELDS = [
        'nama_dokumen', 'kode_dokumen', 'tanggal_berlaku',
        'revisi_ke', 'divisi_id', 'kategori', 'link_dokumen', 'is_active',
    ];

    /** @var array<string, array>  [model_id => old_attributes] */
    private static array $pendingUpdates = [];

    public function created(DokumenIso $dokumen): void
    {
        $this->sendNotification($dokumen, 'dibuat');
    }

    public function updating(DokumenIso $dokumen): void
    {
        // Capture old values before update so we can use them in updated()
        $old = [];
        foreach (self::SIGNIFICANT_FIELDS as $field) {
            if ($dokumen->isDirty($field)) {
                $old[$field] = $dokumen->getOriginal($field);
            }
        }
        if (! empty($old)) {
            self::$pendingUpdates[$dokumen->getKey()] = $old;
        }
    }

    public function updated(DokumenIso $dokumen): void
    {
        $key = $dokumen->getKey();
        $old = self::$pendingUpdates[$key] ?? null;
        unset(self::$pendingUpdates[$key]);

        if (empty($old)) {
            return; // No significant fields changed
        }

        // Old nama for display
        $oldNama = $old['nama_dokumen'] ?? null;
        $this->sendNotification($dokumen, 'diperbarui', $oldNama);
    }

    public function deleted(DokumenIso $dokumen): void
    {
        $this->sendNotification($dokumen, 'dihapus');
    }

    // -------------------------------------------------------------------------

    private function sendNotification(DokumenIso $dokumen, string $aksi, ?string $oldNama = null): void
    {
        if (! $dokumen->divisi_id) {
            return;
        }

        $users = User::whereHas('divisiSubscriptions', function ($q) use ($dokumen) {
            $q->where('divisi_id', $dokumen->divisi_id);
        })->where('is_active', true)->get();

        if ($users->isEmpty()) {
            return;
        }

        $divisi = $dokumen->divisi;

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->queue(new DokumenIsoMail(
                    $dokumen->fresh(['divisi']),
                    $user,
                    $aksi,
                    $oldNama,
                ));
            } catch (\Throwable $e) {
                Log::error('Gagal kirim email notifikasi dokumen ISO', [
                    'user_id' => $user->id,
                    'dokumen_id' => $dokumen->id,
                    'aksi' => $aksi,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
