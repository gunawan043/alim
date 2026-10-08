<?php

namespace App\Services;

use App\Models\PekanEfektif;
use App\Models\Prosem;
use App\Models\ProsemItem;
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
            $weeksTaken = [];

            if ($weekCount === 0 || $jp === 0) {
                $rows[] = [
                    'prota_item_id' => $item->id,
                    'tujuan_pembelajaran_id' => $item->tujuan_pembelajaran_id,
                    'urutan' => $index + 1,
                    'mulai_minggu_ke' => 0,
                    'selesai_minggu_ke' => 0,
                    'jp' => $jp,
                    'keterangan' => $weekCount === 0 ? 'Belum dijadwalkan — Pekan Efektif belum tersedia.' : null,
                    'weeks' => [],
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

                if ($take > 0) {
                    $pekanKe = (int) $effective[$weekIdx]->minggu_ke;
                    $weeksTaken[$pekanKe] = ($weeksTaken[$pekanKe] ?? 0) + $take;
                }

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
                'weeks' => $weeksTaken,
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
                $item = $prosem->items()->create([
                    'prota_item_id' => $row['prota_item_id'],
                    'tujuan_pembelajaran_id' => $row['tujuan_pembelajaran_id'],
                    'urutan' => $row['urutan'],
                    'mulai_minggu_ke' => $row['mulai_minggu_ke'],
                    'selesai_minggu_ke' => $row['selesai_minggu_ke'],
                    'jp' => $row['jp'],
                    'sumber' => ProsemItem::SUMBER_OTOMATIS,
                    'keterangan' => $row['keterangan'],
                ]);

                foreach (($row['weeks'] ?? []) as $pekanKe => $jp) {
                    $item->weeks()->create(['pekan_ke' => (int) $pekanKe, 'jp' => (int) $jp]);
                }
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
                $existing = $prosem->items()->get()->keyBy('prota_item_id');

                foreach ($computed['rows'] as $row) {
                    /** @var \App\Models\ProsemItem|null $item */
                    $item = $row['prota_item_id'] ? $existing->get($row['prota_item_id']) : null;

                    // Penyesuaian manual guru dipertahankan; total JP tetap mengikuti PROTA.
                    // Item tidak pernah dihapus/dibuat ulang agar relasi jurnal tetap utuh.
                    if ($item && $item->isManual()) {
                        $item->forceFill(['jp' => $row['jp']])->save();

                        continue;
                    }

                    if ($item) {
                        $item->forceFill([
                            'tujuan_pembelajaran_id' => $row['tujuan_pembelajaran_id'],
                            'urutan' => $row['urutan'],
                            'mulai_minggu_ke' => $row['mulai_minggu_ke'],
                            'selesai_minggu_ke' => $row['selesai_minggu_ke'],
                            'jp' => $row['jp'],
                            'sumber' => ProsemItem::SUMBER_OTOMATIS,
                            'keterangan' => $row['keterangan'],
                        ])->save();

                        $item->weeks()->delete();
                    } else {
                        $item = $prosem->items()->create([
                            'prota_item_id' => $row['prota_item_id'],
                            'tujuan_pembelajaran_id' => $row['tujuan_pembelajaran_id'],
                            'urutan' => $row['urutan'],
                            'mulai_minggu_ke' => $row['mulai_minggu_ke'],
                            'selesai_minggu_ke' => $row['selesai_minggu_ke'],
                            'jp' => $row['jp'],
                            'sumber' => ProsemItem::SUMBER_OTOMATIS,
                            'keterangan' => $row['keterangan'],
                        ]);
                    }

                    foreach (($row['weeks'] ?? []) as $pekanKe => $jp) {
                        $item->weeks()->create(['pekan_ke' => (int) $pekanKe, 'jp' => (int) $jp]);
                    }
                }
            }

            $prosem->forceFill(['synced_at' => now()])->save();
        });

        return $prosem->fresh();
    }

    /**
     * Validitas distribusi item PROSEM terhadap Pekan Efektif terkini.
     *
     * @param  array<int, int>  $effectiveWeekNumbers
     * @return array{sum: int, valid: bool, invalid_weeks: array<int, int>}
     */
    public function itemValidity(ProsemItem $item, array $effectiveWeekNumbers): array
    {
        $weeks = $item->weeks;
        $sum = (int) $weeks->sum('jp');

        $invalidWeeks = $weeks
            ->filter(fn ($w) => ! in_array((int) $w->pekan_ke, $effectiveWeekNumbers, true))
            ->pluck('pekan_ke')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        return [
            'sum' => $sum,
            'valid' => $weeks->isNotEmpty() && $invalidWeeks === [] && $sum === (int) $item->jp,
            'invalid_weeks' => $invalidWeeks,
        ];
    }

    /**
     * Simpan penyesuaian distribusi manual untuk satu item.
     * Total JP tidak boleh berubah (validasi dilakukan controller).
     *
     * @param  array<int|string, int>  $weeks  [pekan_ke => jp]
     */
    public function saveManualDistribution(ProsemItem $item, array $weeks): ProsemItem
    {
        $weeks = collect($weeks)
            ->mapWithKeys(fn ($jp, $pekan) => [(int) $pekan => (int) $jp])
            ->filter(fn ($jp) => $jp > 0)
            ->sortKeys();

        DB::transaction(function () use ($item, $weeks) {
            $item->weeks()->delete();

            foreach ($weeks as $pekanKe => $jp) {
                $item->weeks()->create(['pekan_ke' => $pekanKe, 'jp' => $jp]);
            }

            $item->forceFill([
                'sumber' => ProsemItem::SUMBER_MANUAL,
                'mulai_minggu_ke' => (int) $weeks->keys()->min(),
                'selesai_minggu_ke' => (int) $weeks->keys()->max(),
            ])->save();

            $item->prosem?->forceFill(['adjusted_at' => now()])->save();
        });

        return $item->fresh(['weeks']);
    }

    /**
     * Kembalikan distribusi item ke hasil otomatis (dari PROTA/ATP).
     */
    public function resetToAutomatic(ProsemItem $item): ProsemItem
    {
        $prosem = $item->prosem;
        $prota = $prosem?->prota;

        if (! $prosem || ! $prota) {
            return $item;
        }

        $computed = $this->buildFromProta($prota);
        $row = collect($computed['rows'])->firstWhere('prota_item_id', $item->prota_item_id);

        if (! $row) {
            return $item;
        }

        DB::transaction(function () use ($item, $row) {
            $item->weeks()->delete();

            foreach (($row['weeks'] ?? []) as $pekanKe => $jp) {
                $item->weeks()->create(['pekan_ke' => (int) $pekanKe, 'jp' => (int) $jp]);
            }

            $item->forceFill([
                'sumber' => ProsemItem::SUMBER_OTOMATIS,
                'mulai_minggu_ke' => $row['mulai_minggu_ke'],
                'selesai_minggu_ke' => $row['selesai_minggu_ke'],
                'jp' => $row['jp'],
                'keterangan' => $row['keterangan'],
            ])->save();
        });

        return $item->fresh(['weeks']);
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
