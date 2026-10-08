<?php

namespace App\Services;

use App\Models\AlurTujuanPembelajaran;
use App\Models\Prota;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ProtaService — PROTA dibangun dari ATP + Pekan Efektif (satu sumber data).
 *
 * Tidak ada input ulang: minggu efektif, JP per minggu, dan JP efektif
 * di-snapshot dari Pekan Efektif + TeachingHoursResolver; item PROTA
 * dihasilkan dari item ATP (TP, BAB/Materi, alokasi JP).
 */
class ProtaService
{
    public function __construct(
        private readonly PekanEfektifService $pekanService,
        private readonly TeachingHoursResolver $hoursResolver,
    ) {}

    /**
     * Hitung snapshot + item PROTA dari ATP (tanpa menyimpan).
     *
     * @return array<string, mixed>
     */
    public function buildFromAtp(AlurTujuanPembelajaran $atp): array
    {
        $atp->loadMissing(['subject', 'gradeLevel', 'items.tujuanPembelajaran.capaianPembelajaran']);

        $semester = $atp->semester === 'genap' ? 2 : 1;
        $summary = $atp->school_id
            ? $this->pekanService->summary($atp->school_id, $atp->academic_year_id, $semester)
            : ['minggu_efektif' => 0, 'is_persisted' => false];

        $mingguEfektif = (int) ($summary['minggu_efektif'] ?? 0);
        $jpPerMinggu = $this->hoursResolver->resolve(null, $atp->subject, null, $atp->grade_level_id);
        $jpEfektif = $jpPerMinggu * $mingguEfektif;

        $items = [];
        foreach ($atp->items as $index => $item) {
            $tp = $item->tujuanPembelajaran;

            $items[] = [
                'tujuan_pembelajaran_id' => $item->tujuan_pembelajaran_id,
                'bab' => $tp?->elemen ?: $tp?->capaianPembelajaran?->elemen ?: null,
                'materi' => $tp?->deskripsi,
                'alokasi_jp' => (int) $item->jp_alokasi,
                'keterangan' => $item->catatan,
                'urutan' => $index + 1,
            ];
        }

        return [
            'minggu_efektif' => $mingguEfektif,
            'jp_per_minggu' => $jpPerMinggu,
            'jp_efektif' => $jpEfektif,
            'items' => $items,
            'summary' => $summary,
        ];
    }

    /**
     * Buat PROTA baru dari ATP (beserta item) — idempotent per ATP.
     */
    public function createFromAtp(AlurTujuanPembelajaran $atp, User $user): Prota
    {
        $computed = $this->buildFromAtp($atp);

        return DB::transaction(function () use ($atp, $user, $computed) {
            $prota = Prota::create([
                'school_id' => $atp->school_id,
                'academic_year_id' => $atp->academic_year_id,
                'semester' => $atp->semester,
                'subject_id' => $atp->subject_id,
                'grade_level_id' => $atp->grade_level_id,
                'fase' => $atp->fase,
                'teacher_id' => $atp->teacher_id ?: $user->id,
                'atp_id' => $atp->id,
                'minggu_efektif' => $computed['minggu_efektif'],
                'jp_per_minggu' => $computed['jp_per_minggu'],
                'jp_efektif' => $computed['jp_efektif'],
                'total_jp' => 0,
                'synced_at' => now(),
                'status' => Prota::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            foreach ($computed['items'] as $item) {
                $prota->items()->create($item);
            }

            $prota->forceFill(['total_jp' => $prota->items()->sum('alokasi_jp')])->save();

            return $prota;
        });
    }

    /**
     * Sinkronkan PROTA dengan sumber data terbaru.
     *
     * @param  bool  $rebuildItems  true → item dibangun ulang dari ATP (perubahan ATP tercermin)
     */
    public function sync(Prota $prota, bool $rebuildItems = false): Prota
    {
        $atp = $prota->atp;

        if (! $atp) {
            $prota->forceFill(['synced_at' => now()])->save();

            return $prota;
        }

        $computed = $this->buildFromAtp($atp);

        DB::transaction(function () use ($prota, $computed, $rebuildItems) {
            if ($rebuildItems) {
                $prota->items()->delete();
                foreach ($computed['items'] as $item) {
                    $prota->items()->create($item);
                }
            }

            $prota->forceFill([
                'minggu_efektif' => $computed['minggu_efektif'],
                'jp_per_minggu' => $computed['jp_per_minggu'],
                'jp_efektif' => $computed['jp_efektif'],
                'total_jp' => $prota->items()->sum('alokasi_jp'),
                'synced_at' => now(),
            ])->save();
        });

        return $prota->fresh();
    }

    /**
     * Apakah PROTA perlu diperbarui? (sumber data berubah setelah snapshot)
     *
     * @return array{stale: bool, reasons: array<int, string>}
     */
    public function staleness(Prota $prota): array
    {
        $reasons = [];
        $atp = $prota->atp;

        if ($atp) {
            $semester = $prota->semester === 'genap' ? 2 : 1;
            $summary = $this->pekanService->summary($prota->school_id, $prota->academic_year_id, $semester);
            $currentWeeks = (int) ($summary['minggu_efektif'] ?? 0);
            $currentJpPerWeek = $this->hoursResolver->resolve(null, $prota->subject, null, $prota->grade_level_id);

            if ($currentWeeks !== (int) $prota->minggu_efektif) {
                $reasons[] = "Pekan Efektif berubah: {$prota->minggu_efektif} → {$currentWeeks} minggu efektif.";
            }

            if ($currentJpPerWeek !== (int) $prota->jp_per_minggu) {
                $reasons[] = "JP per minggu berubah: {$prota->jp_per_minggu} → {$currentJpPerWeek} JP.";
            }

            $atpTotal = (int) $atp->items()->sum('jp_alokasi');
            if ($atpTotal !== (int) $prota->items()->sum('alokasi_jp')) {
                $reasons[] = "Total JP ATP ({$atpTotal}) berbeda dengan item PROTA ({$prota->items()->sum('alokasi_jp')}).";
            }
        }

        return ['stale' => $reasons !== [], 'reasons' => $reasons];
    }
}
