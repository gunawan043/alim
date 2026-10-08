<?php

namespace App\Services;

use App\Models\NilaiSumatif;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu-satunya sumber aturan untuk Sumatif Harian (SH):
 *  - definisi kolom (S1–S6 legacy + kolom dinamis baru)
 *  - baca/tulis nilai SH per siswa (legacy tetap di kolom s1..s6)
 *  - kalkulasi turunan (RS, RSA, NR Murni, NR Final) yang dipakai
 *    wizard, autosave, grid wali kelas, halaman legacy, dan rekap.
 */
class SumatifHarianService
{
    public const LEGACY_IDS = ['s1', 's2', 's3', 's4', 's5', 's6'];

    /**
     * Definisi kolom default = S1–S6 (kompatibilitas).
     *
     * @return array<int, array{id:string,label:string,legacy:bool}>
     */
    public function defaultColumns(): array
    {
        return array_map(
            fn (string $id) => ['id' => $id, 'label' => 'Sumatif '.substr($id, 1), 'legacy' => true],
            self::LEGACY_IDS
        );
    }

    /**
     * Definisi kolom efektif sebuah buku. Kolom legacy selalu disertakan.
     * Kolom dinamis baru (non-legacy) disimpan di teacher_admin_books.sumatif_columns.
     *
     * @return array<int, array{id:string,label:string,legacy:bool}>
     */
    public function columnsFor(?TeacherAdminBook $book): array
    {
        $columns = [];
        $stored = $book?->sumatif_columns;

        if (is_array($stored)) {
            foreach ($stored as $col) {
                if (! is_array($col)) {
                    continue;
                }
                $id = trim((string) ($col['id'] ?? ''));
                if ($id === '') {
                    continue;
                }
                $columns[$id] = [
                    'id' => $id,
                    'label' => trim((string) ($col['label'] ?? '')) ?: strtoupper($id),
                    'legacy' => in_array($id, self::LEGACY_IDS, true),
                ];
            }
        }

        foreach (self::LEGACY_IDS as $id) {
            if (! isset($columns[$id])) {
                $columns[$id] = ['id' => $id, 'label' => 'Sumatif '.substr($id, 1), 'legacy' => true];
            }
        }

        return array_values($columns);
    }

    /**
     * Simpan definisi kolom (tambah, rename, hapus, urutkan).
     * Kolom legacy tidak dapat dihapus — selalu dipertahankan.
     * Menghapus kolom dinamis akan membuang nilainya dari JSON semua siswa
     * lalu menghitung ulang nilai turunan.
     *
     * @param  array<int, array{id?:string,label?:string}>  $columns
     * @return array<int, array{id:string,label:string,legacy:bool}>
     */
    public function saveColumns(TeacherAdminBook $book, array $columns): array
    {
        $previousIds = array_column($this->columnsFor($book), 'id');

        $clean = [];
        $seen = [];

        foreach ($columns as $col) {
            if (! is_array($col)) {
                continue;
            }

            $id = trim((string) ($col['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $isLegacy = in_array($id, self::LEGACY_IDS, true);
            if (! $isLegacy && ! Str::isUuid($id)) {
                continue;
            }

            $label = trim((string) ($col['label'] ?? ''));
            $clean[] = ['id' => $id, 'label' => $label !== '' ? $label : strtoupper($id)];
            $seen[$id] = true;
        }

        // Legacy wajib ada.
        foreach (self::LEGACY_IDS as $id) {
            if (! isset($seen[$id])) {
                $clean[] = ['id' => $id, 'label' => 'Sumatif '.substr($id, 1)];
                $seen[$id] = true;
            }
        }

        $removed = array_values(array_diff($previousIds, array_keys($seen)));

        $book->forceFill(['sumatif_columns' => $clean])->save();

        if (! empty($removed)) {
            $this->pruneExtraValues($book->id, $removed);
            $this->recalcByBook($book->fresh());
        }

        return $this->columnsFor($book->fresh());
    }

    /**
     * Nilai efektif semua kolom untuk satu baris: [columnId => float|null].
     * Legacy dibaca dari kolom fisik s1..s6; kolom baru dari JSON sumatif_harian.
     *
     * @param  array<int, array{id:string,label:string,legacy:bool}>  $columns
     * @return array<string, float|null>
     */
    public function valuesFor(?NilaiSumatif $row, array $columns): array
    {
        $extra = $row?->sumatif_harian;
        $extra = is_array($extra) ? $extra : [];

        $values = [];

        foreach ($columns as $col) {
            $id = $col['id'];

            if (in_array($id, self::LEGACY_IDS, true)) {
                $raw = $row?->{$id};
            } else {
                $raw = $extra[$id] ?? null;
            }

            $values[$id] = ($raw === null || $raw === '') ? null : (float) $raw;
        }

        return $values;
    }

    /**
     * RS = rata-rata semua kolom SH yang terisi (legacy + dinamis).
     *
     * @param  array<string, float|int|string|null>  $values
     */
    public function calcRs(array $values): ?float
    {
        $filled = array_filter(
            $values,
            fn ($v) => $v !== null && $v !== '' && is_numeric($v)
        );

        if (empty($filled)) {
            return null;
        }

        return round(array_sum($filled) / count($filled), 2);
    }

    /**
     * Simpan nilai satu siswa secara TERPADU — semua jalur input memakai method ini.
     *
     * @param  array{
     *     sh?: array<string, float|int|string|null>,
     *     sts?: float|int|string|null,
     *     sas?: float|int|string|null,
     *     raport_sts?: float|int|string|null,
     *     ket?: string|null
     * }  $input
     */
    public function upsertSumatif(TeacherAdminBook $book, string $studentId, array $input): NilaiSumatif
    {
        $columns = $this->columnsFor($book);

        // Deteksi apakah input SH disertakan (form lengkap) atau hanya parsial
        // (mis. simpan SAS saja) — agar nilai SH existing tidak tertimpa kosong.
        $hasShInput = array_key_exists('sh', $input) && is_array($input['sh']);
        $shInput = $hasShInput ? $input['sh'] : [];

        if (! $hasShInput) {
            foreach (self::LEGACY_IDS as $id) {
                if (array_key_exists($id, $input)) {
                    $shInput[$id] = $input[$id];
                    $hasShInput = true;
                }
            }
        }

        $row = NilaiSumatif::firstOrNew([
            'admin_book_id' => $book->id,
            'student_id' => $studentId,
            'semester' => $book->semester,
        ]);

        $row->academic_year_id = $book->academic_year_id;

        $values = [];

        if ($hasShInput) {
            $extra = [];

            foreach ($columns as $col) {
                $id = $col['id'];
                $raw = $shInput[$id] ?? null;
                $val = ($raw === null || $raw === '' || ! is_numeric($raw)) ? null : (float) $raw;
                $values[$id] = $val;

                if (in_array($id, self::LEGACY_IDS, true)) {
                    $row->{$id} = $val;
                } elseif ($val !== null) {
                    $extra[$id] = $val;
                }
            }

            $row->sumatif_harian = ! empty($extra) ? $extra : null;
        } else {
            // Parsial: nilai efektif dari kondisi baris saat ini (termasuk legacy + JSON).
            $values = $this->valuesFor($row, $columns);
        }

        if (array_key_exists('sts', $input)) {
            $row->sts = $this->numericOrNull($input['sts']);
        }
        if (array_key_exists('sas', $input)) {
            $row->sas = $this->numericOrNull($input['sas']);
        }
        if (array_key_exists('raport_sts', $input)) {
            $row->raport_sts = $this->numericOrNull($input['raport_sts']);
        }
        if (array_key_exists('ket', $input)) {
            $ket = trim((string) ($input['ket'] ?? ''));
            $row->ket = $ket !== '' ? $ket : null;
        }

        $this->applyDerived($row, $book, $values);

        $row->save();

        return $row;
    }

    /**
     * Hitung ulang nilai turunan dari kondisi baris saat ini.
     * Dipakai setelah perubahan parsial (mis. STS/KKM/bobot).
     */
    public function recalcDerived(NilaiSumatif $row, ?TeacherAdminBook $book = null): NilaiSumatif
    {
        $book = $book ?? $row->adminBook;

        if (! $book) {
            return $row;
        }

        $columns = $this->columnsFor($book);
        $values = $this->valuesFor($row, $columns);

        $this->applyDerived($row, $book, $values);

        if ($row->isDirty()) {
            $row->save();
        }

        return $row;
    }

    /**
     * Hitung ulang seluruh siswa dalam satu buku.
     */
    public function recalcByBook(TeacherAdminBook $book): int
    {
        $columns = $this->columnsFor($book);
        $updated = 0;

        NilaiSumatif::where('admin_book_id', $book->id)->each(function (NilaiSumatif $row) use ($book, $columns, &$updated) {
            $values = $this->valuesFor($row, $columns);
            $this->applyDerived($row, $book, $values);

            if ($row->isDirty()) {
                $row->save();
                $updated++;
            }
        });

        return $updated;
    }

    /**
     * Buang nilai kolom yang dihapus dari JSON semua siswa (tanpa menyentuh legacy).
     *
     * @param  array<int, string>  $removedIds
     */
    protected function pruneExtraValues(string $bookId, array $removedIds): void
    {
        $extraRemoved = array_values(array_diff($removedIds, self::LEGACY_IDS));

        if (empty($extraRemoved)) {
            return;
        }

        NilaiSumatif::where('admin_book_id', $bookId)->each(function (NilaiSumatif $row) use ($extraRemoved) {
            $extra = $row->sumatif_harian;
            if (! is_array($extra) || empty($extra)) {
                return;
            }

            $changed = false;
            foreach ($extraRemoved as $id) {
                if (array_key_exists($id, $extra)) {
                    unset($extra[$id]);
                    $changed = true;
                }
            }

            if ($changed) {
                $row->sumatif_harian = ! empty($extra) ? $extra : null;
                $row->saveQuietly();
            }
        });
    }

    /**
     * Terapkan rumus turunan yang sama untuk semua jalur:
     *  RS = rata-rata SH terisi
     *  RSA = (raport_sts|sts + sas) / 2
     *  NR Murni = (RS + RSA) / 2
     *  NR Final = (RS×wRs + raport_sts|sts×wSts + SAS×wSas) / 100
     *
     * @param  array<string, float|null>  $values
     */
    protected function applyDerived(NilaiSumatif $row, TeacherAdminBook $book, array $values): void
    {
        $wRs = (float) ($book->nr_final_weight_rs ?? 50.0);
        $wSts = (float) ($book->nr_final_weight_sts ?? 25.0);
        $wSas = (float) ($book->nr_final_weight_sas ?? 25.0);

        $rs = $this->calcRs($values);

        $sts = $row->sts !== null ? (float) $row->sts : null;
        $sas = $row->sas !== null ? (float) $row->sas : null;
        $raportSts = $row->raport_sts !== null ? (float) $row->raport_sts : null;

        $row->rs = $rs;
        $row->rsa = NilaiSumatif::calcRsa($sts, $sas, $raportSts);
        $row->nr_murni = NilaiSumatif::calcNrMurni($rs, $row->rsa !== null ? (float) $row->rsa : null);
        $row->nr_final = NilaiSumatif::calcNrFinal($rs, $sts, $sas, $wRs, $wSts, $wSas, $raportSts);
    }

    protected function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Ringkasan nilai SH untuk rekap/API: [columnId => label + value].
     *
     * @param  array<int, array{id:string,label:string,legacy:bool}>  $columns
     * @return array<int, array{id:string,label:string,value:float|null}>
     */
    public function summaryRows(?NilaiSumatif $row, array $columns): array
    {
        $values = $this->valuesFor($row, $columns);

        return array_map(fn ($col) => [
            'id' => $col['id'],
            'label' => $col['label'],
            'value' => $values[$col['id']] ?? null,
        ], $columns);
    }

    /**
     * Helper untuk query builder: ekspresi MySQL JSON untuk mengambil nilai
     * kolom dinamis tertentu (dipakai bila perlu agregasi SQL).
     */
    public function jsonValueExpression(string $columnId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $columnId);

        return "JSON_UNQUOTE(JSON_EXTRACT(sumatif_harian, '$.{$safe}'))";
    }

    /**
     * Statistik jumlah kolom dinamis yang terisi per buku (untuk rekap validasi).
     */
    public function filledColumnCounts(TeacherAdminBook $book): array
    {
        $columns = $this->columnsFor($book);
        $counts = [];

        NilaiSumatif::where('admin_book_id', $book->id)
            ->get()
            ->each(function (NilaiSumatif $row) use ($columns, &$counts) {
                foreach ($this->valuesFor($row, $columns) as $id => $value) {
                    if ($value !== null) {
                        $counts[$id] = ($counts[$id] ?? 0) + 1;
                    }
                }
            });

        return $counts;
    }
}
