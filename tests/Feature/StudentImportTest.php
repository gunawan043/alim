<?php

namespace Tests\Feature;

use App\Imports\StudentImport;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fase 3 — Import santri: transaksional, validasi kolom wajib,
 * duplikat NISN/NIK/NIS, dan kapasitas rombel.
 */
class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixture();
    }

    public function test_import_membuat_santri_dan_riwayat_rombel(): void
    {
        $import = new StudentImport($this->school->id, $this->group->id);
        $import->collection($this->makeRows([
            ['name' => 'Santri Import Satu', 'nis' => '1001', 'gender' => 'L', 'nisn' => '990001', 'nik' => '3200000000000001'],
            ['name' => 'Santri Import Dua', 'nis' => '1002', 'gender' => 'P', 'nisn' => '990002', 'nik' => '3200000000000002'],
        ]));

        $this->assertSame(2, $import->getSuccessCount());
        $this->assertSame([], $import->getErrors());

        $santri = Student::where('nisn', '990001')->firstOrFail();
        $this->assertSame('Santri Import Satu', $santri->name);
        $this->assertSame('active', $santri->status);

        // Riwayat rombel dengan nomor absen berurutan.
        $histories = StudentClassHistory::where('study_group_id', $this->group->id)
            ->where('academic_year_id', $this->ay->id)
            ->orderBy('attendance_number')
            ->get();

        $this->assertCount(2, $histories);
        $this->assertSame([1, 2], $histories->pluck('attendance_number')->map(fn ($n) => (int) $n)->all());
        $this->assertTrue($histories->every(fn ($h) => (bool) $h->is_active));
    }

    public function test_import_menolak_baris_tanpa_nisn_dan_gender_tidak_valid(): void
    {
        $import = new StudentImport($this->school->id, $this->group->id);
        $import->collection($this->makeRows([
            ['name' => 'Tanpa NISN', 'nis' => '2001', 'gender' => 'L', 'nisn' => ''],
            ['name' => 'Gender Salah', 'nis' => '2002', 'gender' => 'X', 'nisn' => '990003'],
        ]));

        $this->assertSame(0, $import->getSuccessCount());
        $this->assertSame(0, Student::count());

        $errors = implode(' | ', $import->getErrors());
        $this->assertStringContainsString('NISN wajib diisi', $errors);
        $this->assertStringContainsString('jenis kelamin tidak valid', $errors);
    }

    public function test_import_melewati_duplikat_nisn_dan_nis_dalam_sekolah(): void
    {
        Student::create([
            'school_id' => $this->school->id,
            'nisn' => '990010',
            'nis' => '3001',
            'name' => 'Santri Lama',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $import = new StudentImport($this->school->id, $this->group->id);
        $import->collection($this->makeRows([
            ['name' => 'Duplikat NISN', 'nis' => '3002', 'gender' => 'L', 'nisn' => '990010'],
            ['name' => 'Duplikat NIS', 'nis' => '3001', 'gender' => 'P', 'nisn' => '990011'],
            ['name' => 'Valid Baru', 'nis' => '3003', 'gender' => 'L', 'nisn' => '990012'],
        ]));

        $this->assertSame(1, $import->getSuccessCount());
        $this->assertCount(2, $import->getDuplicates());
        $this->assertSame(2, Student::count());
    }

    public function test_import_tidak_membuat_santri_saat_rombel_penuh(): void
    {
        $this->group->update(['capacity' => 1]);

        // Isi rombel sampai penuh.
        $penghuni = Student::create([
            'school_id' => $this->school->id,
            'nisn' => '990020',
            'name' => 'Penghuni Rombel',
            'gender' => 'L',
            'status' => 'active',
        ]);
        StudentClassHistory::create([
            'student_id' => $penghuni->id,
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => true,
            'join_date' => now()->toDateString(),
            'attendance_number' => 1,
        ]);

        $import = new StudentImport($this->school->id, $this->group->id);
        $import->collection($this->makeRows([
            ['name' => 'Tidak Muat', 'nis' => '4001', 'gender' => 'L', 'nisn' => '990021'],
        ]));

        $this->assertSame(0, $import->getSuccessCount());
        $this->assertSame(1, Student::count(), 'Santri baru tidak boleh dibuat saat rombel penuh.');
        $this->assertStringContainsString('penuh', implode(' | ', $import->getErrors()));
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Bangun Collection baris seperti hasil pembacaan Excel
     * (5 baris header di-skip oleh importer, data mulai index 5).
     *
     * @param  array<int, array<string, string>>  $dataRows
     */
    private function makeRows(array $dataRows): Collection
    {
        $rows = collect();
        for ($i = 0; $i < 5; $i++) {
            $rows->push(collect(array_fill(0, 66, null)));
        }

        foreach ($dataRows as $data) {
            $row = array_fill(0, 66, null);
            $row[1] = $data['name'] ?? null;
            $row[2] = $data['nis'] ?? null;
            $row[3] = $data['gender'] ?? null;
            $row[4] = $data['nisn'] ?? null;
            $row[7] = $data['nik'] ?? null;
            $rows->push(collect($row));
        }

        return $rows;
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Import',
            'code' => 'UUIM',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '55556666', 'name' => 'Sekolah Import Uji']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);
    }
}
