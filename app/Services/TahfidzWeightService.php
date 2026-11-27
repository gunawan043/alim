<?php

namespace App\Services;

use App\Models\TahfidzGradebook;
use App\Models\TahfidzWeightSetting;

/**
 * Bobot NR Final mapel tahfidz — sumber tunggal milik DEPARTEMEN.
 *
 * Guru hanya mengisi nilai; bobot ditetapkan departemen sekali per sekolah
 * dan tahun ajaran, terpisah untuk semester ganjil (tanpa SAS) dan genap
 * (dengan SAS). Buku halaqah diberi SALINAN bobot ini agar rapor, leger,
 * dan cetak membaca angka yang sama tanpa query tambahan.
 */
class TahfidzWeightService
{
    /**
     * Bobot semester GANJIL — SAS belum ada.
     */
    public const DEFAULT_GANJIL = [
        'npt' => 25.0,
        'ziyadah' => 20.0,
        'rabt' => 20.0,
        'tikrar' => 10.0,
        'sts' => 25.0,
        'sas' => 0.0,
    ];

    /**
     * Bobot semester GENAP — SAS ikut dinilai.
     */
    public const DEFAULT_GENAP = [
        'npt' => 25.0,
        'ziyadah' => 10.0,
        'rabt' => 10.0,
        'tikrar' => 10.0,
        'sts' => 20.0,
        'sas' => 25.0,
    ];

    /** @var array<string, array<string, float>> */
    private array $cache = [];

    /** @var array<string, bool> sekolah|TA yang sudah disinkronkan pada request ini */
    private array $synced = [];

    /**
     * Bobot bawaan menurut semester.
     *
     * @return array<string, float>
     */
    public function defaultsFor(?string $semester): array
    {
        return $this->normalizeSemester($semester) === 'genap'
            ? self::DEFAULT_GENAP
            : self::DEFAULT_GANJIL;
    }

    /**
     * Bobot yang berlaku: setelan departemen bila ada, selain itu bobot bawaan.
     *
     * @return array<string, float>
     */
    public function forSchool(?string $schoolId, ?string $academicYearId, ?string $semester): array
    {
        $semester = $this->normalizeSemester($semester);

        if (! $schoolId || ! $academicYearId) {
            return $this->defaultsFor($semester);
        }

        $key = $schoolId.'|'.$academicYearId.'|'.$semester;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $setting = TahfidzWeightSetting::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();

        return $this->cache[$key] = $setting
            ? $setting->weights()
            : $this->defaultsFor($semester);
    }

    /**
     * Atribut kolom bobot untuk baris buku halaqah yang baru dibuat.
     *
     * @return array<string, float>
     */
    public function bookAttributes(?string $schoolId, ?string $academicYearId, ?string $semester): array
    {
        $attributes = [];
        foreach ($this->forSchool($schoolId, $academicYearId, $semester) as $key => $value) {
            $attributes['weight_'.$key] = $value;
        }

        return $attributes;
    }

    /**
     * Salin bobot departemen ke satu buku halaqah dan hitung ulang NR Final.
     *
     * @return array<string, float>
     */
    public function applyToBook(TahfidzGradebook $book): array
    {
        $weights = $this->forSchool($book->school_id, $book->academic_year_id, $book->semester);

        $attributes = [];
        foreach ($weights as $key => $value) {
            $attributes['weight_'.$key] = $value;
        }

        $book->update($attributes);
        $book->refresh()->recalcAll();

        return $weights;
    }

    /**
     * Salin bobot departemen ke SELURUH buku halaqah sekolah itu pada TA ini.
     * Dipakai saat departemen menyimpan bobot agar NR lama langsung menyesuaikan.
     */
    public function recalcBooksForSchool(string $schoolId, string $academicYearId): int
    {
        $books = TahfidzGradebook::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        foreach ($books as $book) {
            $this->applyToBook($book);
        }

        $this->synced[$schoolId.'|'.$academicYearId] = true;

        return $books->count();
    }

    /**
     * Penyelarasan ringan: cukup sekali per request per (sekolah × TA).
     * Membuat buku lama (yang dibuat sebelum bobot dikelola departemen)
     * ikut bobot terbaru tanpa harus membuka layar setelan.
     */
    public function syncOnce(string $schoolId, string $academicYearId): void
    {
        $key = $schoolId.'|'.$academicYearId;

        if (isset($this->synced[$key])) {
            return;
        }

        $this->recalcBooksForSchool($schoolId, $academicYearId);
    }

    /**
     * Simpan setelan departemen untuk kedua semester sekaligus.
     *
     * @param  array<string, mixed>  $ganjil
     * @param  array<string, mixed>  $genap
     * @return array<string, string> pesan kesalahan per kolom (kosong = tersimpan)
     */
    public function save(
        ?string $schoolId,
        string $academicYearId,
        array $ganjil,
        array $genap,
        ?string $userId = null,
    ): array {
        $calculator = app(TahfidzGradebookCalculator::class);

        $errors = [];
        foreach (['ganjil' => $ganjil, 'genap' => $genap] as $semester => $weights) {
            // Semester ganjil tidak mengenal SAS: bobot SAS wajib 0, bukan
            // diisi lalu gagal validasi karena totalnya lewat 100%.
            if ($semester === 'ganjil') {
                $weights['sas'] = 0;
            }

            foreach ($calculator->validateWeights($weights) as $field => $message) {
                $errors[$semester.'.'.$field] = $message;
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        $ganjil['sas'] = 0;

        foreach (['ganjil' => $ganjil, 'genap' => $genap] as $semester => $weights) {
            $attributes = ['updated_by' => $userId];
            foreach (TahfidzGradebookCalculator::KEYS as $key) {
                $attributes['weight_'.$key] = (float) $weights[$key];
            }

            TahfidzWeightSetting::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'semester' => $semester,
                ],
                $attributes,
            );
        }

        $this->cache = [];
        $this->synced = [];

        if ($schoolId) {
            $this->recalcBooksForSchool($schoolId, $academicYearId);
        }

        return [];
    }

    /**
     * Setelan tersimpan mentah (null = belum pernah diatur departemen).
     *
     * @return array<string, array<string, float>|null>
     */
    public function storedFor(?string $schoolId, ?string $academicYearId): array
    {
        $result = ['ganjil' => null, 'genap' => null];

        if (! $schoolId || ! $academicYearId) {
            return $result;
        }

        $rows = TahfidzWeightSetting::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        foreach ($rows as $row) {
            $result[$row->semester] = $row->weights();
        }

        return $result;
    }

    private function normalizeSemester(?string $semester): string
    {
        return $semester === 'genap' ? 'genap' : 'ganjil';
    }
}
