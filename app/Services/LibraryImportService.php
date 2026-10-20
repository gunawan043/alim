<?php

namespace App\Services;

use App\Models\LibraryGenre;
use App\Models\LibraryItem;
use App\Models\LibraryMember;
use App\Models\LibraryMemberCard;
use App\Models\LibraryTitle;
use App\Models\LibraryUnit;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LibraryImportService
{
    public function importMembers(LibraryUnit $unit, array $rows): array
    {
        $result = $this->emptyResult();
        [$headers, $rows] = $this->splitHeader($rows);
        $map = $this->headerMap($headers);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->rowData($map, $row);
            if ($this->isBlank($data)) {
                continue;
            }
            $result['total']++;

            try {
                DB::transaction(function () use ($unit, $data) {
                    $nis = $this->value($data, 'nis');
                    $nisn = $this->value($data, 'nisn');
                    $email = $this->value($data, 'email');
                    $jenis = strtolower($this->value($data, 'jenis'));

                    $isGtk = in_array($jenis, ['gtk', 'guru', 'karyawan', 'pegawai'], true)
                        || ($nis === '' && $nisn === '' && $email !== '');

                    $student = null;
                    $user = null;
                    if ($isGtk) {
                        if ($email === '') {
                            throw new \InvalidArgumentException('Email GTK wajib diisi.');
                        }
                        $user = User::where('email', $email)->first();
                        if (! $user) {
                            throw new \InvalidArgumentException('GTK dengan email '.$email.' tidak ditemukan.');
                        }
                        if (LibraryMember::where('library_unit_id', $unit->id)->where('user_id', $user->id)->exists()) {
                            throw new \InvalidArgumentException('GTK sudah menjadi anggota unit ini.');
                        }
                    } else {
                        if ($nis === '' && $nisn === '') {
                            throw new \InvalidArgumentException('NIS atau NISN wajib diisi untuk anggota santri.');
                        }
                        $student = Student::query()
                            ->when($unit->school_id, fn ($q) => $q->where('school_id', $unit->school_id))
                            ->where(function ($q) use ($nis, $nisn) {
                                if ($nis !== '') {
                                    $q->where('nis', $nis);
                                }
                                if ($nisn !== '') {
                                    $q->orWhere('nisn', $nisn);
                                }
                            })->first();
                        if (! $student) {
                            throw new \InvalidArgumentException('Santri dengan NIS/NISN tersebut tidak ditemukan.');
                        }
                        $this->assertStudentEligible($student, $unit);
                        if (LibraryMember::where('library_unit_id', $unit->id)->where('student_id', $student->id)->exists()) {
                            throw new \InvalidArgumentException('Santri sudah menjadi anggota unit ini.');
                        }
                    }

                    $memberNumber = $this->value($data, 'nomor_anggota');
                    if ($memberNumber === '') {
                        $identity = $student?->nis ?: $student?->nisn ?: Str::upper(Str::random(6));
                        $memberNumber = 'LIB-'.preg_replace('/[^A-Za-z0-9]/', '', $unit->code).'-'.$identity;
                        while (LibraryMember::where('member_number', $memberNumber)->exists()) {
                            $memberNumber .= '-'.random_int(1, 99);
                        }
                    } elseif (LibraryMember::where('member_number', $memberNumber)->exists()) {
                        throw new \InvalidArgumentException('Nomor anggota '.$memberNumber.' sudah dipakai.');
                    }

                    $joinedAt = $this->date($this->value($data, 'tanggal_daftar')) ?? today();
                    $member = LibraryMember::create([
                        'library_unit_id' => $unit->id,
                        'student_id' => $student?->id,
                        'user_id' => $user?->id,
                        'member_number' => $memberNumber,
                        'member_type' => $isGtk ? 'gtk' : 'student',
                        'status' => LibraryMember::STATUS_ACTIVE,
                        'joined_at' => $joinedAt,
                        'is_primary' => true,
                    ]);
                    LibraryMemberCard::create([
                        'library_member_id' => $member->id,
                        'card_number' => 'CARD-'.$memberNumber,
                        'status' => LibraryMemberCard::STATUS_ACTIVE,
                        'issued_at' => today(),
                        'valid_until' => now()->addYear()->toDateString(),
                    ]);
                });
                $result['success']++;
            } catch (Throwable $e) {
                $result['failed'][] = ['row' => $rowNumber, 'reason' => $e->getMessage()];
            }
        }

        return $result;
    }

    public function importCatalog(LibraryUnit $unit, array $rows): array
    {
        $result = $this->emptyResult();
        [$headers, $rows] = $this->splitHeader($rows);
        $map = $this->headerMap($headers);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->rowData($map, $row);
            if ($this->isBlank($data)) {
                continue;
            }
            $result['total']++;

            try {
                DB::transaction(function () use ($unit, $data) {
                    $titleName = trim($this->value($data, 'judul'));
                    if ($titleName === '') {
                        throw new \InvalidArgumentException('Judul buku wajib diisi.');
                    }

                    $genreName = trim($this->value($data, 'genre'));
                    $genre = null;
                    if ($genreName !== '') {
                        $genre = LibraryGenre::firstOrCreate(
                            ['school_id' => $unit->school_id, 'name' => $genreName],
                            ['is_active' => true]
                        );
                    }

                    $title = LibraryTitle::where('school_id', $unit->school_id)->where('title', $titleName)->first();
                    if (! $title) {
                        $title = LibraryTitle::create([
                            'school_id' => $unit->school_id,
                            'genre_id' => $genre?->id,
                            'collection_type' => $this->collectionType($this->value($data, 'jenis_koleksi')),
                            'title' => $titleName,
                            'author' => $this->nullable($this->value($data, 'penulis')),
                            'publisher' => $this->nullable($this->value($data, 'penerbit')),
                            'publication_year' => $this->year($this->value($data, 'tahun')),
                            'isbn' => $this->nullable($this->value($data, 'isbn')),
                            'description' => $this->nullable($this->value($data, 'deskripsi')),
                            'is_active' => true,
                        ]);
                    } elseif ($genre && ! $title->genre_id) {
                        $title->update(['genre_id' => $genre->id]);
                    }

                    $code = trim($this->value($data, 'kode_inventaris'));
                    if ($code === '') {
                        $code = $this->nextInventoryCode($unit);
                    }
                    if (LibraryItem::where('inventory_code', $code)->exists()) {
                        throw new \InvalidArgumentException('Kode inventaris '.$code.' sudah dipakai.');
                    }

                    $condition = $this->condition($this->value($data, 'kondisi'));
                    $status = $this->status($this->value($data, 'status'));

                    LibraryItem::create([
                        'library_title_id' => $title->id,
                        'library_unit_id' => $unit->id,
                        'inventory_code' => $code,
                        'shelf_location' => $this->nullable($this->value($data, 'rak')),
                        'condition' => $condition,
                        'status' => $status,
                        'acquisition_date' => $this->date($this->value($data, 'tanggal_perolehan'))?->toDateString(),
                        'price' => $this->number($this->value($data, 'harga')),
                        'notes' => $this->nullable($this->value($data, 'catatan')),
                    ]);
                });
                $result['success']++;
            } catch (Throwable $e) {
                $result['failed'][] = ['row' => $rowNumber, 'reason' => $e->getMessage()];
            }
        }

        return $result;
    }

    private function emptyResult(): array
    {
        return ['total' => 0, 'success' => 0, 'failed' => []];
    }

    private function splitHeader(array $rows): array
    {
        $headers = [];
        foreach (array_shift($rows) ?? [] as $key => $value) {
            $headers[$key] = $value;
        }

        return [$headers, $rows];
    }

    private function headerMap(array $headers): array
    {
        $aliases = [
            'nama' => 'nama', 'name' => 'nama', 'nama_lengkap' => 'nama',
            'nis' => 'nis', 'nomor_induk' => 'nis', 'nomor_induk_santri' => 'nis',
            'nisn' => 'nisn', 'email' => 'email', 'surel' => 'email', 'e_mail' => 'email',
            'jenis' => 'jenis', 'tipe' => 'jenis', 'jenis_anggota' => 'jenis', 'member_type' => 'jenis',
            'nomor_anggota' => 'nomor_anggota', 'no_anggota' => 'nomor_anggota', 'member_number' => 'nomor_anggota',
            'tanggal_daftar' => 'tanggal_daftar', 'tgl_daftar' => 'tanggal_daftar', 'joined_at' => 'tanggal_daftar',
            'judul' => 'judul', 'title' => 'judul', 'nama_buku' => 'judul',
            'penulis' => 'penulis', 'author' => 'penulis', 'pengarang' => 'penulis',
            'penerbit' => 'penerbit', 'publisher' => 'penerbit',
            'tahun' => 'tahun', 'tahun_terbit' => 'tahun', 'publication_year' => 'tahun',
            'genre' => 'genre', 'kategori' => 'genre',
            'jenis_koleksi' => 'jenis_koleksi', 'collection_type' => 'jenis_koleksi', 'koleksi' => 'jenis_koleksi',
            'isbn' => 'isbn', 'kode_inventaris' => 'kode_inventaris', 'kode' => 'kode_inventaris', 'inventory_code' => 'kode_inventaris',
            'rak' => 'rak', 'shelf' => 'rak', 'shelf_location' => 'rak', 'lokasi_rak' => 'rak',
            'kondisi' => 'kondisi', 'condition' => 'kondisi',
            'status' => 'status', 'status_eksemplar' => 'status',
            'tanggal_perolehan' => 'tanggal_perolehan', 'acquisition_date' => 'tanggal_perolehan',
            'harga' => 'harga', 'price' => 'harga',
            'catatan' => 'catatan', 'notes' => 'catatan', 'deskripsi' => 'deskripsi', 'description' => 'deskripsi',
        ];

        $map = [];
        foreach ($headers as $index => $header) {
            $key = Str::of(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header))->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            if ($key !== '') {
                $map[$aliases[$key] ?? $key] = $index;
            }
        }

        return $map;
    }

    private function rowData(array $map, array $row): array
    {
        $data = [];
        foreach ($map as $key => $index) {
            $data[$key] = $row[$index] ?? null;
        }

        return $data;
    }

    private function value(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function isBlank(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function date(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 100000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value);
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function year(string $value): ?int
    {
        if (! preg_match('/\d{4}/', $value, $match)) {
            return null;
        }

        return (int) $match[0];
    }

    private function number(string $value): ?float
    {
        if ($value === '') {
            return null;
        }
        $value = preg_replace('/[^0-9,\.]/', '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function collectionType(string $value): string
    {
        $value = strtolower(trim($value));

        return match (true) {
            in_array($value, ['kitab', 'kitab_referensi'], true) => 'kitab',
            in_array($value, ['referensi', 'reference'], true) => 'referensi',
            in_array($value, ['fiksi', 'fiction'], true) => 'fiksi',
            in_array($value, ['nonfiksi', 'non_fiksi', 'nonfiction'], true) => 'nonfiksi',
            in_array($value, ['pelajaran', 'buku_pelajaran', 'textbook'], true) => 'pelajaran',
            default => 'umum',
        };
    }

    private function condition(string $value): string
    {
        $value = strtolower(str_replace('_', ' ', trim($value)));

        return match (true) {
            in_array($value, ['', 'baik', 'bagus', 'good'], true) => 'baik',
            in_array($value, ['rusak ringan', 'ringan'], true) => 'rusak_ringan',
            in_array($value, ['rusak sedang', 'sedang'], true) => 'rusak_sedang',
            in_array($value, ['rusak berat', 'berat'], true) => 'rusak_berat',
            default => throw new \InvalidArgumentException('Kondisi tidak dikenali: '.$value),
        };
    }

    private function status(string $value): string
    {
        $value = strtolower(str_replace('_', ' ', trim($value)));

        return match (true) {
            in_array($value, ['', 'tersedia', 'available'], true) => LibraryItem::STATUS_AVAILABLE,
            in_array($value, ['perbaikan', 'maintenance'], true) => LibraryItem::STATUS_MAINTENANCE,
            in_array($value, ['hilang', 'lost'], true) => LibraryItem::STATUS_LOST,
            in_array($value, ['ditarik', 'withdrawn'], true) => LibraryItem::STATUS_WITHDRAWN,
            default => throw new \InvalidArgumentException('Status tidak dikenali atau tidak boleh diimport: '.$value),
        };
    }

    private function nextInventoryCode(LibraryUnit $unit): string
    {
        $prefix = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $unit->code), 0, 5));
        $next = LibraryItem::where('library_unit_id', $unit->id)->count() + 1;
        do {
            $code = $prefix.'-'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (LibraryItem::where('inventory_code', $code)->exists());

        return $code;
    }

    private function assertStudentEligible(Student $student, LibraryUnit $unit): void
    {
        if ($unit->gender_scope !== 'mixed' && strtoupper((string) $student->gender) !== strtoupper((string) $unit->gender_scope)) {
            throw new \InvalidArgumentException('Gender santri tidak sesuai dengan unit perpustakaan.');
        }

        $history = StudentClassHistory::with('studyGroup.gradeLevel')->where('student_id', $student->id)->latest()->first();
        $level = $history?->studyGroup?->gradeLevel?->level;
        if ($unit->level_scope === 'sd' && $level && $level > 6) {
            throw new \InvalidArgumentException('Unit perpustakaan khusus jenjang SD.');
        }
        if ($unit->level_scope === 'smp' && $level && ($level < 7 || $level > 9)) {
            throw new \InvalidArgumentException('Unit perpustakaan khusus jenjang SMP.');
        }
        if ($unit->level_scope === 'sd_smp' && $level && $level > 9) {
            throw new \InvalidArgumentException('Unit perpustakaan khusus jenjang SD–SMP.');
        }
    }
}
