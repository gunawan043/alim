<?php

namespace App\Services;

use App\Models\Peraturan;
use App\Models\PeraturanReadLog;
use App\Models\Pelanggaran;
use App\Models\PelanggaranLog;
use Illuminate\Support\Facades\DB;

class PeraturanService
{
    public function catatPembacaan(string $peraturanId, string $userId, ?string $ip = null): PeraturanReadLog
    {
        return PeraturanReadLog::updateOrCreate(
            ['peraturan_id' => $peraturanId, 'user_id' => $userId],
            ['read_at' => now(), 'ip_address' => $ip]
        );
    }

    public function catatPelanggaran(array $data): PelanggaranLog
    {
        return DB::transaction(function () use ($data) {
            $log = PelanggaranLog::create($data);
            return $log;
        });
    }

    public function rekapPoinUser(string $userId): array
    {
        $logs = PelanggaranLog::with('pelanggaran')->where('user_id', $userId)->get();
        $total = $logs->sum(fn($l) => $l->pelanggaran->poin);
        return [
            'total_poin' => $total,
            'jumlah_pelanggaran' => $logs->count(),
            'per_jenis' => [
                'ringan' => $logs->filter(fn($l) => $l->pelanggaran->jenis === 'ringan')->sum(fn($l) => $l->pelanggaran->poin),
                'sedang' => $logs->filter(fn($l) => $l->pelanggaran->jenis === 'sedang')->sum(fn($l) => $l->pelanggaran->poin),
                'berat' => $logs->filter(fn($l) => $l->pelanggaran->jenis === 'berat')->sum(fn($l) => $l->pelanggaran->poin),
            ],
            'log' => $logs,
        ];
    }
}