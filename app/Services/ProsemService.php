<?php

namespace App\Services;

use App\Models\PekanEfektif;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProsemService — PROSEM = distribusi PROTA/ATP ke bulan & pekan efektif
 * dari Kalender Pendidikan (via Pekan Efektif). Tidak ada kalender kedua.
 *
 * Minggu berjenis libur (0 hari efektif) tidak dipakai; pekan ujian /
 * kegiatan sekolah tetap dipakai tetapi ditandai pada keterangan.
 */
class ProsemService
{
    public function __construct(private readonly ProtaService $protaService) {}

    /**
     * Baris pekan efektif untuk sekolah + tahun ajaran + semester dokumen.
     */
    public function pekanEfektif(Prosem|Prota $doc): Collection
    {
        $semester = $doc->semester === 'genap' ? 2 : 1;

        return PekanEfektif::query()
            ->bySchool($doc->school_id)
            ->byAcademicYear($doc->academic_year_id)
            ->bySemester($semester)
            ->orderBy('minggu_ke')
            ->get();
    }

    /**
     * Hitung distribusi item PROTA terhadap pekan efektif (tanpa menyimpan).
     *
     * @return array<string, mixed>
     */
    public function buildFromProta(Prota $prota): array
    {
        $weeks = $this->pekanEfektif($prota);
        $effective = $weeks->filter(fn (PekanEfektif $p) => (int) $p->hari_efektif > 0)->values();

        $items = $prota->items()->with('tujuanPembelajaran')->orderBy('urutan')->get();

        $capacity = (int) $prota->jp_per_minggu;
        if ($capacity <= 0 && $effective->isNotEmpty()) {
            $total = max(1, (int) $items->sum('alokasi_jp'));
            $capacity = (int) max(1, ceil($total / $effective->count()));
        }

        $rows = [];
        $weekIdx = 0;
        $remainingCapacity = $effective->isNotEmpty() ? $capacity : 0;
        $overflowJp = 0;
        $weekCount = $effective->count();

        foreach ($items as $index => $item) {
            $jp = max(0, (int) $item->alokasi_jp);
            $startIdx = $weekIdx;
            $endIdx = $weekIdx;
            $remainingJp = $jp;

            if ($weekCount === 0 || $jp === 0) {
                $rows[] = [
                    'prota_item_id' => $item->id,
                    'tujuan_pembelajaran_id' => $item->tujuan_pembelajaran_id,
                    'urutan' => $index + 1,
                    'mulai_minggu_ke' => 0,
                    'selesai_minggu_ke' => 0,
                    'jp' => $jp,
                    'keterangan' => $weekCount === 0 ? 'Belum dijadwalkan — Pekan Efektif belum tersedia.' : null,
                ];

                continue;
            }

            while ($remainingJp > 0 && $weekIdx < $weekCount) {
                if ($remainingCapacity <= 0) {
                    $weekIdx++;
                    $remainingCapacity = $capacity;
                    continue;
                }

                $take = min($remainingJp, $remainingCapacity);
                $remainingJp -= $take;
                $remainingCapacity -= $take;
                $endIdx = $weekIdx;
            }

            $startWeek = $effective[$startIdx] ?? $effective->last();
            $endWeek = $effective[min($endIdx, $weekCount - 1)] ?? $effective->last();

            $span = $effective->slice($startIdx, max(1, $endIdx - $startIdx + 1));
            $special = $span
                ->filter(fn (PekanEfektif $p) => in_array($p->jenis, ['ujian', 'kegiatan_sekolah'], true))
                ->pluck('jenis')
                ->unique()
                ->map(fn ($j) => $j === 'ujian' ? 'pekan ujian' : 'kegiatan sekolah')
                ->values()
                ->all();

            $keterangan = $special ? ucfirst(implode(', ', $special)) : null;

            if ($remainingJp > 0) {
                $overflowJp += $remainingJp;
                $keterangan = trim(($keterangan ? $keterangan.'; ' : '')."{$remainingJp} JP melebihi pekan efektif");
            }

            $rows[] = [
                'prota_item_id' => $item->id,
                'tujuan_pembelajaran_id' => $item->tujuan_pembelajaran_id,
                'urutan' => $index + 1,
                'mulai_minggu_ke' => (int) $startWeek->minggu_ke,
                'selesai_minggu_ke' => (int) $endWeek->minggu_ke,
                'jp' => $jp,
                'keterangan' => $keterangan ? mb_substr($keterangan, 0, 255) : null,
            ];
        }

        return [
            'rows' => $rows,
            'weeks' => $weeks,
            'effective_weeks' => $effective,
            'capacity' => $capacity,
            'overflow_jp' => $overflowJp,
        ];
    }

    /**
     * Buat PROSEM baru dari PROTA (beserta distribusi pekan) — idempotent per PROTA.
     */
    public function createFromProta(Prota $prota, User $user): Prosem
    {
        $computed = $this->buildFromProta($prota);

        return DB::transaction(function () use ($prota, $user, $computed) {
            $prosem = Prosem::create([
                'prota_id' => $prota->id,
                'school_id' => $prota->school_id,
                'academic_year_id' => $prota->academic_year_id,
                'semester' => $prota->semester,
                'subject_id' => $prota->subject_id,
                'grade_level_id' => $prota->grade_level_id,
                'teacher_id' => $prota->teacher_id ?: $user->id,
                'synced_at' => now(),
                'status' => Prosem::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            foreach ($computed['rows'] as $row) {
                $prosem->items()->create($row);
            }

            return $prosem;
        });
    }

    /**
     * Sinkronkan PROSEM dengan PROTA/Pekan Efektif terbaru.
     */
    public function sync(Prosem $prosem, bool $regenerate = true): Prosem
    {
        $prota = $prosem->prota;

        if (! $prota) {
            $prosem->forceFill(['synced_at' => now()])->save();

            return $prosem;
        }

        // Pastikan snapshot PROTA ikut segar (tanpa rebuild item).
        $this->protaService->sync($prota);

        $computed = $this->buildFromProta($prota->fresh());

        DB::transaction(function () use ($prosem, $computed, $regenerate) {
            if ($regenerate) {
                $prosem->items()->delete();
                foreach ($computed['rows'] as $row) {
                    $prosem->items()->create($row);
                }
            }

            $prosem->forceFill(['synced_at' => now()])->save();
        });

        return $prosem->fresh();
    }

    /**
     * Apakah PROSEM perlu diperbarui?
     *
     * @return array{stale: bool, reasons: array<int, string>}
     */
    public function staleness(Prosem $prosem): array
    {
        $reasons = [];
        $prota = $prosem->prota;

        if (! $prota) {
            return ['stale' => false, 'reasons' => []];
        }

        if ($prosem->synced_at && $prota->updated_at && $prota->updated_at->gt($prosem->synced_at)) {
            $reasons[] = 'PROTA diperbarui setelah PROSEM dibuat.';
        }

        $protaTotal = (int) $prota->items()->sum('alokasi_jp');
        $prosemTotal = (int) $prosem->items()->sum('jp');

        if ($protaTotal !== $prosemTotal) {
            $reasons[] = "Total JP PROTA ({$protaTotal}) berbeda dengan PROSEM ({$prosemTotal}).";
        }

        return ['stale' => $reasons !== [], 'reasons' => $reasons];
    }
}
