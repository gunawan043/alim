<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Models\BankSoal;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\Soal;

/**
 * Paket Soal sebagai NASKAH (Tahap 2):
 *  - Komposisi eksplisit: jenis/jumlah/bobot/urutan.
 *  - Generate butir dari komposisi (termasuk isian & uraian/esai).
 *  - Snapshot butir saat final — naskah kebal perubahan soal sumber.
 */
class PaketNaskahService
{
    public const JENIS_LABEL = [
        'pg' => 'Pilihan Ganda',
        'mcc' => 'PG Kompleks',
        'bs' => 'Benar/Salah',
        'jodoh' => 'Menjodohkan',
        'isian' => 'Isian',
        'uraian' => 'Uraian/Esai',
    ];

    public const JENIS_VALID = ['pg', 'mcc', 'bs', 'jodoh', 'isian', 'uraian'];

    /**
     * Simpan komposisi naskah (ternormalisasi, urutan mengikuti baris).
     *
     * @param  array<int, array{jenis?: string, jumlah?: int|string, bobot?: float|string}>  $rows
     */
    public function simpanKomposisi(PaketSoal $paket, array $rows): void
    {
        $normalized = collect($rows)
            ->map(fn ($row) => [
                'jenis' => strtolower(trim((string) ($row['jenis'] ?? ''))),
                'jumlah' => (int) ($row['jumlah'] ?? 0),
                'bobot' => (float) ($row['bobot'] ?? 0),
            ])
            ->filter(fn ($row) => in_array($row['jenis'], self::JENIS_VALID, true) && $row['jumlah'] > 0)
            ->values()
            ->map(fn ($row, $index) => $row + ['urutan' => $index + 1])
            ->values()
            ->all();

        $paket->forceFill(['komposisi' => $normalized])->save();
    }

    /**
     * Validasi butir terhadap komposisi naskah.
     *
     * @return array{ok: bool, errors: array<int, string>}
     */
    public function validasi(PaketSoal $paket): array
    {
        $komposisi = $paket->komposisi ?? [];

        if ($komposisi === []) {
            return ['ok' => true, 'errors' => []];
        }

        $items = $paket->items()->with('soal:id,tipe_soal')->get();
        $errors = [];

        foreach ($komposisi as $row) {
            $jenis = $row['jenis'] ?? null;
            $jumlah = (int) ($row['jumlah'] ?? 0);

            $tersedia = $items->filter(function (PaketSoalItem $item) use ($jenis) {
                return ($item->jenis_soal ?? $item->soal?->tipe_soal) === $jenis;
            })->count();

            if ($tersedia !== $jumlah) {
                $errors[] = sprintf(
                    '%s: %d dari %d butir',
                    self::JENIS_LABEL[$jenis] ?? $jenis,
                    $tersedia,
                    $jumlah
                );
            }
        }

        return ['ok' => $errors === [], 'errors' => $errors];
    }

    /**
     * Isi butir dari komposisi: pilih soal approved sesuai jenis dari bank
     * (termasuk isian & uraian). Menggantikan seluruh butir paket.
     */
    public function generateDariKomposisi(PaketSoal $paket, ?string $bankId = null): int
    {
        $komposisi = $paket->komposisi ?? [];

        if ($komposisi === []) {
            return 0;
        }

        $kisi = $paket->kisiKisi;
        $bankId = $bankId ?: BankSoal::query()
            ->when($kisi?->subject_id, fn ($q) => $q->where('subject_id', $kisi->subject_id))
            ->when($kisi?->school_id, fn ($q) => $q->where('school_id', $kisi->school_id))
            ->value('id');

        if (! $bankId) {
            return 0;
        }

        $paket->items()->delete();

        $nomor = 1;
        $created = 0;

        foreach ($komposisi as $row) {
            $pool = Soal::query()
                ->where('bank_soal_id', $bankId)
                ->where('status', 'approved')
                ->where('tipe_soal', $row['jenis'])
                ->inRandomOrder()
                ->limit((int) $row['jumlah'])
                ->get();

            foreach ($pool as $soal) {
                PaketSoalItem::create([
                    'paket_soal_id' => $paket->id,
                    'soal_id' => $soal->id,
                    'urutan' => $nomor,
                    'nomor' => $nomor,
                    'jenis_soal' => $row['jenis'],
                    'bobot_override' => $row['bobot'] > 0 ? $row['bobot'] : ($soal->bobot_default ?? 1),
                ]);
                $nomor++;
                $created++;
            }
        }

        $this->sinkronMeta($paket);
        $paket->recomputeTotals();

        return $created;
    }

    /**
     * Sinkronkan nomor & jenis butir dari data soal (untuk butir yang belum
     * memiliki metadata, mis. hasil pilih otomatis lama).
     */
    public function sinkronMeta(PaketSoal $paket): void
    {
        $items = $paket->items()->with('soal:id,tipe_soal')->orderBy('urutan')->get();

        foreach ($items as $item) {
            $perluNomor = $item->nomor === null;
            $perluJenis = $item->jenis_soal === null && $item->soal?->tipe_soal;

            if ($perluNomor || $perluJenis) {
                $item->forceFill(array_filter([
                    'nomor' => $perluNomor ? $item->urutan : null,
                    'jenis_soal' => $perluJenis ? $item->soal->tipe_soal : null,
                ]))->save();
            }
        }
    }

    /**
     * Bekukan butir menjadi naskah: snapshot pertanyaan, opsi/kunci,
     * pembahasan, bobot, nomor, dan jenis.
     */
    public function snapshot(PaketSoal $paket): int
    {
        $count = 0;

        foreach ($paket->items()->with(['soal.options'])->orderBy('urutan')->get() as $item) {
            $soal = $item->soal;

            if (! $soal) {
                continue;
            }

            $item->forceFill([
                'konten_snapshot' => [
                    'nomor' => $item->nomor ?? $item->urutan,
                    'jenis' => $item->jenis_soal ?? $soal->tipe_soal,
                    'soal_id' => $soal->id,
                    'tp_id' => $soal->tp_id,
                    'wacana' => $soal->wacanaPayload(),
                    'pertanyaan' => $soal->pertanyaan,
                    'opsi' => $soal->options->map(fn ($option) => [
                        'label' => $option->label,
                        'teks' => $option->teks_opsi,
                        'kunci' => (bool) $option->is_correct,
                    ])->values()->all(),
                    'pembahasan' => $soal->pembahasan,
                    'bobot' => (float) ($item->bobot_override ?? $soal->bobot_default ?? 0),
                ],
            ])->save();

            if ($item->nomor === null) {
                $item->forceFill(['nomor' => $item->urutan])->save();
            }
            if ($item->jenis_soal === null && $soal->tipe_soal) {
                $item->forceFill(['jenis_soal' => $soal->tipe_soal])->save();
            }

            $count++;
        }

        if ($count > 0) {
            $paket->forceFill(['snapshot_at' => now()])->save();
        }

        return $count;
    }
}
