<?php

namespace App\Services\Tahfidz;

use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzGroupTeacher;
use App\Models\User;
use App\Services\TahfidzAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor halaqah dari berkas Excel.
 *
 * Tiga tahap terpisah dengan sengaja:
 *   1. read()     — ubah baris berkas menjadi struktur { halaqah => [anggota] }
 *   2. preview()  — cocokkan ke database TANPA menulis apa pun
 *   3. commit()   — tulis hasil pratinjau dalam satu transaksi
 *
 * Pratinjau dan penyimpanan memakai perencanaan yang sama (plan()), sehingga
 * apa yang dilihat pengguna persis sama dengan yang akan disimpan.
 */
class HalaqahImportService
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    private ?School $school = null;

    private ?string $academicYearId = null;

    public function __construct(private readonly TahfidzAccess $access) {}

    /**
     * Baca hasil array dari PhpSpreadsheet (sudah dinormalisasi ke heading row).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function read(array $rows, School $school, string $academicYearId): static
    {
        $this->school = $school;
        $this->academicYearId = $academicYearId;
        $this->rows = $this->normalizeRows($rows);

        return $this;
    }

    /**
     * Normalisasi nama kolom: huruf kecil, spasi/dash menjadi garis bawah.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, string>>
     */
    private function normalizeRows(array $rows): array
    {
        $map = [
            'nama_halaqah' => 'name',
            'halaqah' => 'name',
            'nama' => 'name',
            'kelas' => 'kelas',
            'tingkat' => 'kelas',
            'guru' => 'guru',
            'guru_tahfidz' => 'guru',
            'nis' => 'nis',
            'nama_santri' => 'santri',
            'santri' => 'santri',
        ];

        $normalized = [];

        foreach ($rows as $row) {
            $clean = [];

            foreach ($row as $key => $value) {
                $k = Str::of((string) $key)->lower()->replace([' ', '-'], '_')->trim('_')->toString();

                if (isset($map[$k])) {
                    $clean[$map[$k]] = trim((string) $value);
                }
            }

            if (($clean['name'] ?? '') === '' && ($clean['santri'] ?? '') === '' && ($clean['nis'] ?? '') === '') {
                continue;
            }

            $normalized[] = $clean;
        }

        return $normalized;
    }

    /**
     * Bangun rencana: setiap halaqah beserta anggota dan statusnya.
     * Tidak menulis ke database.
     *
     * @return array{
     *     groups: array<int, array<string, mixed>>,
     *     errors: array<int, array<string, mixed>>,
     *     totals: array<string, int>
     * }
     */
    public function plan(): array
    {
        $this->assertReady();

        $groups = [];
        $errors = [];

        foreach ($this->rows as $index => $row) {
            $line = $index + 2; // baris 1 = judul kolom
            $name = $row['name'] ?? '';

            if ($name === '') {
                $errors[] = ['line' => $line, 'halaqah' => '—', 'message' => 'Nama halaqah kosong.'];

                continue;
            }

            $key = Str::lower($name);

            if (! isset($groups[$key])) {
                $groups[$key] = $this->planGroup($name, $row, $line, $errors);
            }

            // Kelas & guru pada baris lanjutan halaqah yang sama harus konsisten.
            if (($row['kelas'] ?? '') !== '' && $groups[$key]['kelas_raw'] !== '' && $row['kelas'] !== $groups[$key]['kelas_raw']) {
                $errors[] = [
                    'line' => $line,
                    'halaqah' => $name,
                    'message' => 'Kelas berbeda dengan baris sebelumnya untuk halaqah yang sama ('.$groups[$key]['kelas_raw'].' vs '.$row['kelas'].').',
                ];

                continue;
            }

            if (($row['guru'] ?? '') !== '' && $groups[$key]['guru_raw'] !== '' && $row['guru'] !== $groups[$key]['guru_raw']) {
                $errors[] = [
                    'line' => $line,
                    'halaqah' => $name,
                    'message' => 'Guru berbeda dengan baris sebelumnya untuk halaqah yang sama.',
                ];

                continue;
            }

            if (isset($groups[$key]['error'])) {
                continue;
            }

            if (($row['nis'] ?? '') === '' && ($row['santri'] ?? '') === '') {
                continue; // baris halaqah tanpa anggota — sah
            }

            $member = $this->resolveStudent($row, $line, $name, $errors);

            if ($member === null) {
                continue;
            }

            if (! isset($groups[$key]['member_ids'][$member['id']])) {
                $groups[$key]['member_ids'][$member['id']] = $member['name'];
            }
        }

        // Buang halaqah yang baris pertamanya gagal.
        $groups = array_filter($groups, fn ($g) => ! isset($g['error']));

        $totals = [
            'halaqah' => count($groups),
            'baru' => count(array_filter($groups, fn ($g) => $g['action'] === 'create')),
            'diperbarui' => count(array_filter($groups, fn ($g) => $g['action'] === 'update')),
            'anggota' => array_sum(array_map(fn ($g) => count($g['member_ids']), $groups)),
            'error' => count($errors),
        ];

        return ['groups' => array_values($groups), 'errors' => $errors, 'totals' => $totals];
    }

    /**
     * Rencana untuk satu halaqah (baris pertama dengan nama tersebut).
     */
    private function planGroup(string $name, array $row, int $line, array &$errors): array
    {
        $base = [
            'name' => $name,
            'line' => $line,
            'kelas_raw' => $row['kelas'] ?? '',
            'guru_raw' => $row['guru'] ?? '',
            'member_ids' => [],
            'kelas' => null,
            'guru_id' => null,
            'action' => 'create',
            'existing_id' => null,
        ];

        // Kelas: terima nama ("Kelas 1") maupun angka level ("1").
        if ($base['kelas_raw'] !== '') {
            $grade = $this->resolveGradeLevel($base['kelas_raw']);

            if (! $grade) {
                $base['error'] = true;
                $errors[] = ['line' => $line, 'halaqah' => $name, 'message' => "Kelas \"{$base['kelas_raw']}\" tidak ada di satuan pendidikan ini."];

                return $base;
            }

            $base['kelas'] = $grade->id;
        }

        // Guru: wajib dan harus Guru Tahfidz di satuan pendidikan ini.
        $teacher = $this->resolveTeacher($base['guru_raw']);

        if (! $teacher) {
            $base['error'] = true;
            $errors[] = [
                'line' => $line,
                'halaqah' => $name,
                'message' => $base['guru_raw'] === ''
                    ? 'Guru halaqah kosong.'
                    : "Guru \"{$base['guru_raw']}\" bukan Guru Tahfidz di satuan pendidikan ini.",
            ];

            return $base;
        }

        $base['guru_id'] = $teacher->id;

        // Upsert: nama + tahun ajaran sama di sekolah yang sama.
        $existing = TahfidzGroup::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->academicYearId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($existing) {
            $base['action'] = 'update';
            $base['existing_id'] = $existing->id;
        }

        return $base;
    }

    /**
     * Cocokkan santri: NIS dulu, lalu nama persis. Kembalikan null bila gagal
     * (dan catat alasannya), sehingga baris lain tetap diproses.
     *
     * @return array{id: string, name: string}|null
     */
    private function resolveStudent(array $row, int $line, string $halaqah, array &$errors): ?array
    {
        $nis = $row['nis'] ?? '';
        $nama = $row['santri'] ?? '';

        $query = Student::query()
            ->where('school_id', $this->school->id)
            ->where('status', 'active');

        if ($nis !== '') {
            $found = (clone $query)->where('nis', $nis)->get(['id', 'name']);

            if ($found->isEmpty()) {
                $errors[] = ['line' => $line, 'halaqah' => $halaqah, 'message' => "NIS {$nis} tidak ditemukan di satuan pendidikan ini."];

                return null;
            }

            if ($found->count() > 1) {
                $errors[] = ['line' => $line, 'halaqah' => $halaqah, 'message' => "NIS {$nis} dipakai lebih dari satu santri; tambahkan nama santri untuk memastikan."];

                return null;
            }

            return ['id' => $found->first()->id, 'name' => $found->first()->name];
        }

        $found = $query->whereRaw('LOWER(name) = ?', [Str::lower($nama)])->get(['id', 'name']);

        if ($found->isEmpty()) {
            $errors[] = ['line' => $line, 'halaqah' => $halaqah, 'message' => "Santri bernama \"{$nama}\" tidak ditemukan."];

            return null;
        }

        if ($found->count() > 1) {
            $errors[] = [
                'line' => $line,
                'halaqah' => $halaqah,
                'message' => "Ada {$found->count()} santri bernama \"{$nama}\". Isi kolom NIS agar tidak salah pilih.",
            ];

            return null;
        }

        return ['id' => $found->first()->id, 'name' => $found->first()->name];
    }

    private function resolveGradeLevel(string $raw): ?GradeLevel
    {
        $value = Str::of($raw)->lower()->replace(['kelas', 'tingkat'], '')->trim()->toString();

        return GradeLevel::query()
            ->where('school_id', $this->school->id)
            ->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [Str::lower($raw)])
                ->orWhere('level', is_numeric($value) ? (int) $value : -1))
            ->first();
    }

    private function resolveTeacher(string $name): ?User
    {
        if ($name === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->where('is_active', true)
            ->whereHas('employment', fn ($q) => $q->where('school_id', $this->school->id))
            ->get()
            ->first(fn (User $user) => $this->access->isTahfidzTeacher($user));
    }

    /**
     * Simpan rencana. Setiap halaqah ditulis dalam transaksi sendiri agar
     * satu kegagalan tidak membatalkan halaqah lain.
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array{created: int, updated: int, members: int, failed: array<int, string>}
     */
    public function commit(array $groups, ?string $actorId = null): array
    {
        $created = 0;
        $updated = 0;
        $members = 0;
        $failed = [];

        foreach ($groups as $plan) {
            try {
                $result = DB::transaction(function () use ($plan, $actorId) {
                    $group = $plan['action'] === 'update' && $plan['existing_id']
                        ? TahfidzGroup::find($plan['existing_id'])
                        : null;

                    $gender = $this->school->school_gender ?: 'putra';
                    $coordinatorId = $this->access
                        ->coordinatorForSchool($this->school->id, $this->academicYearId)?->id;

                    if ($group) {
                        $group->update([
                            'grade_level_id' => $plan['kelas'] ?? $group->grade_level_id,
                            'teacher_id' => $plan['guru_id'],
                            'gender' => $gender,
                            'coordinator_id' => $coordinatorId ?? $group->coordinator_id,
                        ]);
                    } else {
                        $group = TahfidzGroup::create([
                            'work_unit_id' => $this->school->work_unit_id,
                            'school_id' => $this->school->id,
                            'study_group_id' => null,
                            'grade_level_id' => $plan['kelas'],
                            'academic_year_id' => $this->academicYearId,
                            'name' => $plan['name'],
                            'gender' => $gender,
                            'teacher_id' => $plan['guru_id'],
                            'coordinator_id' => $coordinatorId,
                            'is_active' => true,
                        ]);
                    }

                    $this->syncTeacher($group);

                    $attached = $this->attachMembers($group, array_keys($plan['member_ids']));

                    return ['created' => $group->wasRecentlyCreated, 'members' => $attached];
                });

                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                }

                $members += $result['members'];
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $plan['name'];
            }
        }

        return ['created' => $created, 'updated' => $updated, 'members' => $members, 'failed' => $failed];
    }

    /**
     * Pastikan guru utama tercatat sebagai pengampu aktif.
     */
    private function syncTeacher(TahfidzGroup $group): void
    {
        TahfidzGroupTeacher::query()
            ->where('tahfidz_group_id', $group->id)
            ->where('user_id', $group->teacher_id)
            ->exists()
            || TahfidzGroupTeacher::create([
                'tahfidz_group_id' => $group->id,
                'user_id' => $group->teacher_id,
                'role' => 'guru_utama',
                'can_input_setoran' => true,
                'is_active' => true,
            ]);
    }

    /**
     * Tambahkan santri (hanya menambah — anggota lama tidak dikeluarkan).
     * Santri yang sudah aktif di halaqah lain pada tahun ajaran ini dipindahkan.
     *
     * @param  array<int, string>  $studentIds
     */
    private function attachMembers(TahfidzGroup $group, array $studentIds): int
    {
        $added = 0;

        foreach ($studentIds as $studentId) {
            $alreadyHere = TahfidzGroupMember::query()
                ->where('tahfidz_group_id', $group->id)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $this->academicYearId)
                ->where('status', 'aktif')
                ->exists();

            if ($alreadyHere) {
                continue;
            }

            // Satu tingkat dengan halaqah — halaqah boleh lintas rombel.
            if ($group->grade_level_id) {
                $studentGrade = StudentClassHistory::query()
                    ->where('student_id', $studentId)
                    ->where('is_active', true)
                    ->get()
                    ->map(fn ($row) => $row->studyGroup?->grade_level_id)
                    ->filter()
                    ->first();

                if ($studentGrade !== null && (string) $studentGrade !== (string) $group->grade_level_id) {
                    continue;
                }
            }

            TahfidzGroupMember::query()
                ->where('student_id', $studentId)
                ->where('academic_year_id', $this->academicYearId)
                ->where('status', 'aktif')
                ->where('tahfidz_group_id', '!=', $group->id)
                ->update(['status' => 'pindah_halaqah', 'leave_date' => now()->toDateString()]);

            TahfidzGroupMember::create([
                'tahfidz_group_id' => $group->id,
                'student_id' => $studentId,
                'academic_year_id' => $this->academicYearId,
                'join_date' => now()->toDateString(),
                'status' => 'aktif',
            ]);

            $added++;
        }

        return $added;
    }

    private function assertReady(): void
    {
        if (! $this->school || ! $this->academicYearId) {
            throw new \RuntimeException('Satuan pendidikan dan tahun ajaran wajib ditetapkan sebelum impor.');
        }
    }
}
