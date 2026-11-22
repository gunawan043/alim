<?php

namespace App\Services;

use App\Models\NilaiSumatif;
use App\Models\TeacherAdminBook;

/**
 * Komponen nilai STS/SAS per buku mapel (mis. Praktik & Tulis).
 *
 * Bila sebuah buku belum punya komponen, fitur ini tidak aktif dan kolom
 * tunggal sts/sas tetap dipakai seperti sebelumnya. Bila aktif, nilai akhir
 * sts/sas = rata-rata komponen yang terisi.
 */
class SumatifKomponenService
{
    public const JENIS = ['sts', 'sas'];

    /**
     * Komponen bawaan saat fitur pertama kali diaktifkan.
     *
     * @return array<int, array{id:string,label:string}>
     */
    public function preset(): array
    {
        return [
            ['id' => 'praktik', 'label' => 'Praktik'],
            ['id' => 'tulis', 'label' => 'Tulis'],
        ];
    }

    /**
     * Definisi komponen efektif sebuah buku. Kosong = fitur tidak aktif.
     *
     * @return array<int, array{id:string,label:string}>
     */
    public function columnsFor(?TeacherAdminBook $book, string $jenis): array
    {
        if (! $book || ! in_array($jenis, self::JENIS, true)) {
            return [];
        }

        $stored = $book->{$jenis.'_columns'};

        return is_array($stored) ? $this->normalize($stored) : [];
    }

    public function hasColumns(?TeacherAdminBook $book, string $jenis): bool
    {
        return $this->columnsFor($book, $jenis) !== [];
    }

    /**
     * Simpan definisi komponen (tambah/rename/hapus/urutkan).
     * Menghapus komponen akan membuang nilainya dari JSON semua siswa lalu
     * menghitung ulang nilai akhir.
     *
     * @param  array<int, array{id?:string,label?:string}>  $columns
     * @return array<int, array{id:string,label:string}>
     */
    public function saveColumns(TeacherAdminBook $book, string $jenis, array $columns): array
    {
        if (! in_array($jenis, self::JENIS, true)) {
            return [];
        }

        $previousIds = array_column($this->columnsFor($book, $jenis), 'id');
        $clean = $this->normalize($columns);
        $newIds = array_column($clean, 'id');

        $book->forceFill([$jenis.'_columns' => $clean !== [] ? $clean : null])->save();
        $book->refresh();

        $removed = array_values(array_diff($previousIds, $newIds));

        if ($removed !== []) {
            $this->pruneValues($book, $jenis, $removed);
        }

        // Nilai akhir dihitung ulang: komponen baru tanpa nilai membuat
        // sts/sas kembali kosong sampai komponennya diisi.
        $this->recalcByBook($book, $jenis);

        return $this->columnsFor($book, $jenis);
    }

    /**
     * Nilai semua komponen untuk satu baris siswa: [columnId => float|null].
     *
     * @param  array<int, array{id:string,label:string}>  $columns
     * @return array<string, float|null>
     */
    public function valuesFor(?NilaiSumatif $row, array $columns, string $jenis): array
    {
        $stored = $row?->{$jenis.'_components'};
        $stored = is_array($stored) ? $stored : [];

        $values = [];

        foreach ($columns as $col) {
            $raw = $stored[$col['id']] ?? null;
            $values[$col['id']] = ($raw === null || $raw === '' || ! is_numeric($raw))
                ? null
                : (float) $raw;
        }

        return $values;
    }

    /**
     * Rata-rata komponen yang terisi. Null bila belum ada yang diisi.
     *
     * @param  array<string, float|int|string|null>  $values
     */
    public function average(array $values): ?float
    {
        $filled = array_filter(
            $values,
            fn ($v) => $v !== null && $v !== '' && is_numeric($v)
        );

        if ($filled === []) {
            return null;
        }

        return round(array_sum($filled) / count($filled), 2);
    }

    /**
     * Set sts/sas satu baris dari komponennya, lalu simpan bila berubah.
     */
    public function recalcRow(NilaiSumatif $row, TeacherAdminBook $book, string $jenis): void
    {
        $columns = $this->columnsFor($book, $jenis);

        if ($columns === []) {
            return;
        }

        $row->{$jenis} = $this->average($this->valuesFor($row, $columns, $jenis));
    }

    /**
     * Hitung ulang sts/sas seluruh siswa dalam satu buku.
     *
     * Baris yang belum punya satu pun nilai komponen dibiarkan apa adanya:
     * saat komponen baru diaktifkan di tengah semester, nilai tunggal yang
     * sudah ada tidak boleh hilang sebelum komponennya diisi.
     */
    public function recalcByBook(TeacherAdminBook $book, string $jenis): int
    {
        $columns = $this->columnsFor($book, $jenis);

        if ($columns === []) {
            return 0;
        }

        $sumatif = app(SumatifHarianService::class);
        $updated = 0;

        NilaiSumatif::where('admin_book_id', $book->id)->each(function (NilaiSumatif $row) use ($columns, $book, $jenis, $sumatif, &$updated) {
            $average = $this->average($this->valuesFor($row, $columns, $jenis));

            if ($average === null) {
                return;
            }

            if ((string) $row->{$jenis} !== (string) $average) {
                $row->{$jenis} = $average;
                $sumatif->recalcDerived($row, $book);
                $updated++;
            }
        });

        return $updated;
    }

    /**
     * Buang nilai komponen yang dihapus dari JSON semua siswa.
     *
     * @param  array<int, string>  $removedIds
     */
    protected function pruneValues(TeacherAdminBook $book, string $jenis, array $removedIds): void
    {
        $key = $jenis.'_components';

        NilaiSumatif::where('admin_book_id', $book->id)->each(function (NilaiSumatif $row) use ($key, $removedIds) {
            $stored = $row->{$key};

            if (! is_array($stored) || $stored === []) {
                return;
            }

            $changed = false;
            foreach ($removedIds as $id) {
                if (array_key_exists($id, $stored)) {
                    unset($stored[$id]);
                    $changed = true;
                }
            }

            if ($changed) {
                $row->{$key} = $stored !== [] ? $stored : null;
                $row->saveQuietly();
            }
        });
    }

    /**
     * @param  array<int, mixed>  $columns
     * @return array<int, array{id:string,label:string}>
     */
    protected function normalize(array $columns): array
    {
        $clean = [];
        $seen = [];

        foreach ($columns as $col) {
            if (! is_array($col)) {
                continue;
            }

            $id = trim((string) ($col['id'] ?? ''));
            if ($id === '' || isset($seen[$id]) || ! preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $id)) {
                continue;
            }

            $label = trim((string) ($col['label'] ?? ''));
            $clean[] = ['id' => $id, 'label' => $label !== '' ? $label : strtoupper($id)];
            $seen[$id] = true;
        }

        return $clean;
    }
}
