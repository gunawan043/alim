<?php

namespace App\Services;

use App\Models\Pelatihan;
use App\Models\PelatihanPeserta;
use App\Models\PelatihanSertifikat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PelatihanService
{
    public function daftarkanPeserta(string $pelatihanId, array $userIds): int
    {
        $count = 0;
        foreach ($userIds as $userId) {
            $created = PelatihanPeserta::firstOrCreate(
                ['pelatihan_id' => $pelatihanId, 'user_id' => $userId],
                ['status_kehadiran' => 'daftar']
            );
            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    public function prosesSertifikat(PelatihanPeserta $peserta, array $data): PelatihanSertifikat
    {
        return DB::transaction(function () use ($peserta, $data) {
            $sertifikat = PelatihanSertifikat::updateOrCreate(
                ['pelatihan_peserta_id' => $peserta->id],
                [
                    'nomor_sertifikat' => $data['nomor_sertifikat'] ?? ('CERT-'.Str::upper(Str::random(8))),
                    'tanggal_terbit' => $data['tanggal_terbit'] ?? now(),
                    'tanggal_expired' => $data['tanggal_expired'] ?? null,
                    'dokumen_path' => $data['dokumen_path'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            return $sertifikat;
        });
    }

    public function updateStatusPeserta(string $pesertaId, string $status): PelatihanPeserta
    {
        $peserta = PelatihanPeserta::findOrFail($pesertaId);
        $peserta->update(['status_kehadiran' => $status]);

        return $peserta->fresh();
    }

    public function getStatistikPelatihan(?string $tahun = null): array
    {
        $query = Pelatihan::query();
        if ($tahun) {
            $query->whereYear('tanggal_mulai', $tahun);
        }

        return [
            'total' => $query->count(),
            'rencana' => (clone $query)->where('status', 'rencana')->count(),
            'berlangsung' => (clone $query)->where('status', 'berlangsung')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
            'total_peserta' => PelatihanPeserta::when($tahun, fn ($q) => $q->whereHas('pelatihan', fn ($qq) => $qq->whereYear('tanggal_mulai', $tahun)))->count(),
            'total_biaya' => (clone $query)->sum('biaya_per_peserta'),
        ];
    }
}
