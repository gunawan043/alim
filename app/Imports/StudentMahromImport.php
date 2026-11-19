<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentMahrom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import data mahrom/wali santri dari Excel/CSV.
 *
 * Kunci pencocokan: NISN (utama) atau NIS. Peran (ayah/ibu/wali/...) yang
 * sudah ada akan DIPERBARUI — bukan diduplikasi — sehingga data hasil import
 * selalu sinkron dengan modul asrama dan portal wali.
 */
class StudentMahromImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    private const RELATIONSHIPS = [
        'ayah', 'ibu', 'kakak', 'adik', 'paman', 'bibi', 'kakek', 'nenek',
        'suami', 'istri', 'sepupu', 'wali', 'anak', 'lainnya',
    ];

    public function __construct(
        private readonly ?string $schoolId = null,
        private readonly ?array $allowedStudentIds = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $maxMahrom = (int) config('alim.max_mahrom', StudentMahrom::MAX_PER_STUDENT);

        $studentQuery = Student::query()
            ->withoutGlobalScope('school_context')
            ->when($this->schoolId, fn ($q) => $q->where('school_id', $this->schoolId))
            ->when($this->allowedStudentIds !== null, fn ($q) => $q->whereIn('id', $this->allowedStudentIds));

        $students = $studentQuery->get(['id', 'school_id', 'nisn', 'nis', 'name']);

        $byNisn = $students->filter(fn ($s) => filled($s->nisn))
            ->keyBy(fn ($s) => strtolower(trim((string) $s->nisn)));
        $byNis = $students->filter(fn ($s) => filled($s->nis))
            ->keyBy(fn ($s) => strtolower(trim((string) $s->nis)));

        foreach ($rows as $index => $row) {
            $rowNumber = (int) $index + 2;
            $data = $row->toArray();

            $name = trim((string) ($data['nama_mahrom'] ?? ''));
            $nisn = trim((string) ($data['nisn'] ?? ''));
            $nis = trim((string) ($data['nis'] ?? ''));

            if ($name === '' && $nisn === '' && $nis === '') {
                continue; // baris kosong
            }

            try {
                if ($name === '') {
                    throw new \RuntimeException('Nama mahrom wajib diisi.');
                }

                $student = $byNisn->get(strtolower($nisn)) ?? $byNis->get(strtolower($nis));

                if (! $student) {
                    throw new \RuntimeException('Santri dengan NISN/NIS tersebut tidak ditemukan pada akses Anda.');
                }

                $relationship = $this->normalizeRelationship($data['hubungan'] ?? null);

                if ($relationship === null) {
                    throw new \RuntimeException('Hubungan tidak valid. Gunakan: ayah, ibu, wali, kakak, adik, paman, bibi, kakek, nenek, sepupu, lainnya.');
                }

                $phone = $this->normalizePhone($data['no_hp'] ?? null);

                if ($phone !== null && ! preg_match('/^\+?[0-9]{8,20}$/', $phone)) {
                    throw new \RuntimeException('Nomor HP tidak valid (gunakan 8–20 digit angka).');
                }

                $nik = trim((string) ($data['nik'] ?? ''));
                if ($nik !== '' && mb_strlen($nik) > 30) {
                    throw new \RuntimeException('NIK maksimal 30 karakter.');
                }

                $address = trim((string) ($data['alamat'] ?? ''));
                $notes = trim((string) ($data['catatan'] ?? ''));
                $isPrimary = $this->toBoolean($data['utama'] ?? null);
                $isActive = $this->toBoolean($data['aktif'] ?? null, true);

                // NIK unik: bila sudah dipakai mahrom lain, kosongkan agar tidak gagal.
                if ($nik !== '' && StudentMahrom::where('id_number', $nik)->exists()) {
                    $existingWithNik = StudentMahrom::where('id_number', $nik)
                        ->where('student_id', $student->id)
                        ->where('relationship', $relationship)
                        ->exists();

                    if (! $existingWithNik) {
                        $nik = '';
                    }
                }

                DB::transaction(function () use (
                    $student, $name, $relationship, $phone, $nik, $address, $notes,
                    $isPrimary, $isActive, $maxMahrom
                ) {
                    $mahrom = StudentMahrom::where('student_id', $student->id)
                        ->where('relationship', $relationship)
                        ->first();

                    if ($mahrom) {
                        // Sinkron: perbarui data yang tersedia; jangan kosongkan
                        // nomor HP/alamat lama hanya karena kolom file kosong.
                        $payload = ['name' => $name];

                        if ($phone !== null) {
                            $payload['phone'] = $phone;
                        }
                        if ($nik !== '') {
                            $payload['id_number'] = $nik;
                        }
                        if ($address !== '') {
                            $payload['address'] = $address;
                        }
                        if ($notes !== '') {
                            $payload['notes'] = $notes;
                        }
                        if ($isActive !== null) {
                            $payload['is_active'] = $isActive;
                        }

                        $mahrom->update($payload);

                        if ($isPrimary === true) {
                            StudentMahrom::where('student_id', $student->id)
                                ->where('id', '!=', $mahrom->id)
                                ->update(['is_primary' => false]);
                            $mahrom->update(['is_primary' => true]);
                        }

                        $this->updated++;

                        return;
                    }

                    $existingCount = StudentMahrom::where('student_id', $student->id)->count();

                    if ($existingCount >= $maxMahrom) {
                        throw new \RuntimeException("Maksimal {$maxMahrom} mahrom per santri sudah tercapai.");
                    }

                    $hasPrimary = StudentMahrom::where('student_id', $student->id)
                        ->where('is_primary', true)
                        ->exists();

                    $makePrimary = $isPrimary === true || ! $hasPrimary;

                    if ($makePrimary) {
                        StudentMahrom::where('student_id', $student->id)->update(['is_primary' => false]);
                    }

                    StudentMahrom::create([
                        'student_id' => $student->id,
                        'name' => $name,
                        'relationship' => $relationship,
                        'phone' => $phone,
                        'id_number' => $nik !== '' ? $nik : null,
                        'address' => $address !== '' ? $address : null,
                        'notes' => $notes !== '' ? $notes : null,
                        'is_active' => $isActive ?? true,
                        'is_primary' => $makePrimary,
                    ]);

                    $this->created++;
                });
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    private function normalizeRelationship(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $aliases = [
            'ayah' => 'ayah', 'bapak' => 'ayah', 'abi' => 'ayah', 'father' => 'ayah',
            'ibu' => 'ibu', 'ummi' => 'ibu', 'mama' => 'ibu', 'mother' => 'ibu',
            'wali' => 'wali', 'orang tua' => 'wali', 'orangtua' => 'wali', 'guardian' => 'wali',
            'kakak' => 'kakak', 'adik' => 'adik',
            'paman' => 'paman', 'om' => 'paman', 'bibi' => 'bibi', 'tante' => 'bibi',
            'kakek' => 'kakek', 'nenek' => 'nenek',
            'suami' => 'suami', 'istri' => 'istri', 'sepupu' => 'sepupu',
            'anak' => 'anak', 'lainnya' => 'lainnya', 'lain' => 'lainnya', 'other' => 'lainnya',
        ];

        $key = preg_replace('/[^a-z ]/', '', $value);
        $key = trim(preg_replace('/\s+/', ' ', $key));

        return $aliases[$key] ?? (in_array($key, self::RELATIONSHIPS, true) ? $key : null);
    }

    private function normalizePhone(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9+]/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    private function toBoolean(mixed $value, ?bool $default = null): ?bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'ya', 'y', 'true', 'aktif', 'utama', 'yes'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'tidak', 'n', 'false', 'nonaktif', 'no'], true)) {
            return false;
        }

        return $default;
    }
}
