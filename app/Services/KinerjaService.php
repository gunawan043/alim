<?php

namespace App\Services;

use App\Models\KinerjaPenilaian;
use App\Models\KinerjaSkor;
use App\Models\KinerjaIndikator;
use App\Models\KinerjaPeriode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KinerjaService
{
    public function buatAtauUpdatePenilaian(string $userId, string $periodeId, array $skors, ?string $penilaiId = null): KinerjaPenilaian
    {
        return DB::transaction(function () use ($userId, $periodeId, $skors, $penilaiId) {
            $penilaian = KinerjaPenilaian::updateOrCreate(
                ['user_id' => $userId, 'kinerja_periode_id' => $periodeId],
                [
                    'penilai_id' => $penilaiId,
                    'tanggal_penilaian' => now(),
                    'status' => 'dinilai',
                ]
            );

            foreach ($skors as $indikatorId => $skor) {
                KinerjaSkor::updateOrCreate(
                    ['kinerja_penilaian_id' => $penilaian->id, 'kinerja_indikator_id' => $indikatorId],
                    ['skor' => $skor['skor'] ?? 0, 'catatan' => $skor['catatan'] ?? null]
                );
            }

            $total = KinerjaSkor::where('kinerja_penilaian_id', $penilaian->id)->avg('skor') ?? 0;

            $penilaian->update([
                'total_skor' => $total,
                'nilai_huruf' => KinerjaPenilaian::hitungNilaiHuruf($total),
                'kategori_hasil' => KinerjaPenilaian::hitungKategori($total),
            ]);

            return $penilaian->fresh(['skor.indikator']);
        });
    }

    public function rekapPerPeriode(KinerjaPeriode $periode): array
    {
        $penilaians = KinerjaPenilaian::with('user', 'periode')
            ->where('kinerja_periode_id', $periode->id)
            ->whereNotNull('total_skor')
            ->get();

        return [
            'total' => $penilaians->count(),
            'rata_skor' => $penilaians->avg('total_skor') ?? 0,
            'distribusi' => [
                'A' => $penilaians->where('nilai_huruf', 'A')->count(),
                'B' => $penilaians->where('nilai_huruf', 'B')->count(),
                'C' => $penilaians->where('nilai_huruf', 'C')->count(),
                'D' => $penilaians->where('nilai_huruf', 'D')->count(),
            ],
            'per_user' => $penilaians->map(fn($p) => [
                'user' => $p->user->name,
                'skor' => $p->total_skor,
                'huruf' => $p->nilai_huruf,
                'kategori' => $p->kategori_hasil,
            ])->sortByDesc('skor')->values()->all(),
        ];
    }
}