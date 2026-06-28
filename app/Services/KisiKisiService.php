<?php

namespace App\Services;

use App\Models\KisiKisiSoal;
use App\Models\KisiKisiSoalItem;
use App\Models\TujuanPembelajaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KisiKisiService
{
    public const DEFAULT_KOGNITIF_DIST = ['C2_memahami' => 30, 'C3_menerapkan' => 50, 'C4_menganalisis' => 20];

    public const DEFAULT_KESULITAN_DIST = ['mudah' => 30, 'sedang' => 50, 'sulit' => 20];

    /**
     * Create a new kisi-kisi header + its items.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KisiKisiSoal
    {
        $validator = Validator::make($data, [
            'subject_id' => 'required|exists:subjects,id',
            'school_id' => 'required|exists:schools,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'judul' => 'required|string|max:150',
            'jenis_ujian' => 'required|in:sts,sas,ulangan_harian,try_out,latihan',
            'tingkat_sekolah' => 'required|in:sd,smp,sma',
            'peminatan' => 'nullable|in:ipa,ips,bahasa',
            'total_soal_target' => 'required|integer|min:1|max:200',
            'total_bobot_target' => 'required|numeric|min:1|max:1000',
            'distribusi_kognitif' => 'nullable|array',
            'distribusi_kesulitan' => 'nullable|array',
            'created_by' => 'nullable|exists:users,id',
            'items' => 'nullable|array',
        ]);

        $validator->validate();

        return DB::transaction(function () use ($data) {
            $kisi = KisiKisiSoal::create([
                'subject_id' => $data['subject_id'],
                'school_id' => $data['school_id'],
                'grade_level_id' => $data['grade_level_id'],
                'academic_year_id' => $data['academic_year_id'],
                'semester' => $data['semester'],
                'judul' => $data['judul'],
                'jenis_ujian' => $data['jenis_ujian'],
                'tingkat_sekolah' => $data['tingkat_sekolah'],
                'peminatan' => $data['peminatan'] ?? null,
                'total_soal_target' => $data['total_soal_target'],
                'total_bobot_target' => $data['total_bobot_target'],
                'distribusi_kognitif' => $data['distribusi_kognitif'] ?? self::DEFAULT_KOGNITIF_DIST,
                'distribusi_kesulitan' => $data['distribusi_kesulitan'] ?? self::DEFAULT_KESULITAN_DIST,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->syncItems($kisi, $data['items'] ?? []);

            return $kisi->fresh(['items.tujuanPembelajaran']);
        });
    }

    /**
     * Sync items (replace all).
     *
     * @param  array<int, array{tp_id:string,level_kognitif:string,jumlah_soal:int,bobot_per_soal:float}>  $items
     */
    public function syncItems(KisiKisiSoal $kisi, array $items): void
    {
        $kisi->items()->delete();
        foreach ($items as $item) {
            KisiKisiSoalItem::create([
                'kisi_kisi_soal_id' => $kisi->id,
                'tp_id' => $item['tp_id'],
                'level_kognitif' => $item['level_kognitif'],
                'jumlah_soal' => $item['jumlah_soal'],
                'bobot_per_soal' => $item['bobot_per_soal'],
            ]);
        }
    }

    /**
     * Validate the kisi-kisi against target totals.
     * Returns array of validation issues (empty if valid).
     */
    public function validateAgainstTarget(KisiKisiSoal $kisi): array
    {
        $issues = [];
        $items = $kisi->items()->get();

        $totalSoal = $items->sum('jumlah_soal');
        if ($totalSoal !== $kisi->total_soal_target) {
            $issues[] = "Total butir soal ({$totalSoal}) tidak sesuai target ({$kisi->total_soal_target}).";
        }

        $totalBobot = $items->sum(fn ($i) => $i->jumlah_soal * $i->bobot_per_soal);
        if (abs($totalBobot - $kisi->total_bobot_target) > 0.01) {
            $issues[] = "Total bobot ({$totalBobot}) tidak sesuai target ({$kisi->total_bobot_target}).";
        }

        $distKog = $items->groupBy('level_kognitif')->map->sum('jumlah_soal');
        $expectedKog = $kisi->distribusi_kognitif ?? [];
        foreach ($expectedKog as $level => $expectedPct) {
            $expected = round(($expectedPct / 100) * $kisi->total_soal_target);
            $actual = $distKog[$level] ?? 0;
            if (abs($actual - $expected) > 1) {
                $issues[] = "Distribusi kognitif {$level}: aktual {$actual}, target {$expected}.";
            }
        }

        return $issues;
    }

    /**
     * Auto-generate items suggestion from a kisi-kisi's distribusi fields.
     * Returns array of ['tp_id' => ..., 'level_kognitif' => ..., 'jumlah_soal' => ..., 'bobot_per_soal' => ...].
     */
    public function suggestItems(KisiKisiSoal $kisi): array
    {
        $totalSoal = $kisi->total_soal_target;
        $tps = TujuanPembelajaran::where('subject_id', $kisi->subject_id)
            ->where('grade_level_id', $kisi->grade_level_id)
            ->where('academic_year_id', $kisi->academic_year_id)
            ->where('semester', $kisi->semester)
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get();

        if ($tps->isEmpty()) {
            return [];
        }

        $perTp = max(1, intdiv($totalSoal, $tps->count()));
        $bobotPerSoal = round($kisi->total_bobot_target / $totalSoal, 2);

        $distKog = $kisi->distribusi_kognitif ?? self::DEFAULT_KOGNITIF_DIST;
        $levels = array_keys($distKog);

        $items = [];
        $i = 0;
        foreach ($tps as $tp) {
            $items[] = [
                'tp_id' => $tp->id,
                'level_kognitif' => $levels[$i % count($levels)],
                'jumlah_soal' => $perTp,
                'bobot_per_soal' => $bobotPerSoal,
            ];
            $i++;
        }

        return $items;
    }
}
