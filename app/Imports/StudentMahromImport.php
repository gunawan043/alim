<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentMahrom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import data mahrom/wali santri.
 *
 * Mendukung dua format:
 *  1. LEBAR (utama) — satu baris per santri; kolom "Mahrom 1..N - ..." ke samping.
 *     Format ini tidak menduplikasi data santri.
 *  2. PANJANG (kompatibilitas file lama) — satu baris per mahrom dengan kolom
 *     nisn/nis/nama_mahrom/hubungan/no_hp/...
 *
 * Peran yang sudah ada (ayah/ibu/wali/...) akan DIPERBARUI, bukan diduplikasi.
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

        // Deteksi format dari baris pertama yang terbaca.
        $firstRow = $rows->first();
        $firstArray = $firstRow instanceof Collection ? $firstRow->toArray() : (array) $firstRow;
        $isWide = array_key_exists('mahrom_1_nama', $firstArray)
            || array_key_exists('mahrom_1_hubungan', $firstArray);

        foreach ($rows as $index => $row) {
            $rowNumber = (int) $index + 2;

            if (! $row instanceof Collection) {
                $row = collect($row);
            }

            try {
                if ($isWide) {
                    $this->processWideRow($row, $rowNumber, $byNisn, $byNis, $maxMahrom);
                } else {
                    $this->processLongRow($row, $rowNumber, $byNisn, $byNis, $maxMahrom);
                }
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    /**
     * Format panjang: satu baris = satu mahrom.
     */
    private function processLongRow(Collection $row, int $rowNumber, Collection $byNisn, Collection $byNis, int $maxMahrom): void
    {
        $data = $row->toArray();

        $name = trim((string) ($data['nama_mahrom'] ?? ''));
        $nisn = trim((string) ($data['nisn'] ?? ''));
        $nis = trim((string) ($data['nis'] ?? ''));

        if ($name === '' && $nisn === '' && $nis === '') {
            return; // baris kosong
        }

        if ($name === '') {
            throw new \RuntimeException('Nama mahrom wajib diisi.');
        }

        $student = $this->findStudent($byNisn, $byNis, $nisn, $nis);

        if (! $student) {
            throw new \RuntimeException('Santri dengan NISN/NIS tersebut tidak ditemukan pada akses Anda.');
        }

        $relationship = $this->normalizeRelationship($data['hubungan'] ?? null);

        if ($relationship === null) {
            throw new \RuntimeException('Hubungan tidak valid. Gunakan: ayah, ibu, wali, kakak, adik, paman, bibi, kakek, nenek, sepupu, lainnya.');
        }

        $result = $this->upsert(
            student: $student,
            name: $name,
            relationship: $relationship,
            phone: $this->normalizePhone($data['no_hp'] ?? null),
            nik: trim((string) ($data['nik'] ?? '')),
            address: trim((string) ($data['alamat'] ?? '')),
            notes: trim((string) ($data['catatan'] ?? '')),
            isPrimary: $this->toBoolean($data['utama'] ?? null),
            isActive: $this->toBoolean($data['aktif'] ?? null, true),
            maxMahrom: $maxMahrom,
        );

        $this->{$result}++;
    }

    /**
     * Format lebar: satu baris = satu santri, mahrom di kolom samping.
     */
    private function processWideRow(Collection $row, int $rowNumber, Collection $byNisn, Collection $byNis, int $maxMahrom): void
    {
        $data = $row->toArray();

        $nisn = trim((string) ($data['nisn'] ?? ''));
        $nis = trim((string) ($data['nis'] ?? ''));

        if ($nisn === '' && $nis === '') {
            return; // baris kosong
        }

        $student = $this->findStudent($byNisn, $byNis, $nisn, $nis);

        if (! $student) {
            throw new \RuntimeException('Santri dengan NISN/NIS tersebut tidak ditemukan pada akses Anda.');
        }

        for ($slot = 1; $slot <= $maxMahrom; $slot++) {
            $name = trim((string) ($data["mahrom_{$slot}_nama"] ?? ''));
            $relationshipRaw = trim((string) ($data["mahrom_{$slot}_hubungan"] ?? ''));

            if ($name === '' && $relationshipRaw === '') {
                continue; // slot kosong
            }

            if ($name === '') {
                $this->errors[] = "Baris {$rowNumber} Mahrom {$slot}: nama mahrom wajib diisi.";

                continue;
            }

            $relationship = $this->normalizeRelationship($relationshipRaw);

            if ($relationship === null) {
                $this->errors[] = "Baris {$rowNumber} Mahrom {$slot}: hubungan tidak valid.";

                continue;
            }

            $phone = $this->normalizePhone($data["mahrom_{$slot}_no_hp"] ?? null);

            if ($phone !== null && ! preg_match('/^\+?[0-9]{8,20}$/', $phone)) {
                $this->errors[] = "Baris {$rowNumber} Mahrom {$slot}: nomor HP tidak valid.";

                continue;
            }

            try {
                $result = $this->upsert(
                    student: $student,
                    name: $name,
                    relationship: $relationship,
                    phone: $phone,
                    nik: trim((string) ($data["mahrom_{$slot}_nik"] ?? '')),
                    address: trim((string) ($data["mahrom_{$slot}_alamat"] ?? '')),
                    notes: '',
                    isPrimary: $this->toBoolean($data["mahrom_{$slot}_utama"] ?? null),
                    isActive: true,
                    maxMahrom: $maxMahrom,
                );

                $this->{$result}++;
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$rowNumber} Mahrom {$slot}: ".$e->getMessage();
            }
        }
    }

    private function findStudent(Collection $byNisn, Collection $byNis, string $nisn, string $nis): ?Student
    {
        return $byNisn->get(strtolower($nisn)) ?? $byNis->get(strtolower($nis));
    }

    /**
     * @return string 'created'|'updated'
     */
    private function upsert(
        Student $student,
        string $name,
        string $relationship,
        ?string $phone,
        string $nik,
        string $address,
        string $notes,
        ?bool $isPrimary,
        ?bool $isActive,
        int $maxMahrom,
    ): string {
        if ($nik !== '' && mb_strlen($nik) > 30) {
            throw new \RuntimeException('NIK maksimal 30 karakter.');
        }

        // NIK unik: bila sudah dipakai mahrom lain, kosongkan agar tidak gagal.
        if ($nik !== '') {
            $usedByOther = StudentMahrom::where('id_number', $nik)
                ->where(fn ($q) => $q->where('student_id', '!=', $student->id)
                    ->orWhere('relationship', '!=', $relationship))
                ->exists();

            if ($usedByOther) {
                $nik = '';
            }
        }

        return DB::transaction(function () use (
            $student, $name, $relationship, $phone, $nik, $address, $notes,
            $isPrimary, $isActive, $maxMahrom
        ) {
            $mahrom = StudentMahrom::where('student_id', $student->id)
                ->where('relationship', $relationship)
                ->first();

            if ($mahrom) {
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

                return 'updated';
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

            return 'created';
        });
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
