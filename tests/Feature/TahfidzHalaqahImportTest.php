<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkAdditionalTask;
use App\Models\GtkEmployment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Tahfidz\HalaqahImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzHalaqahImportTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $unit;

    private School $school;

    private AcademicYear $year;

    private User $coordinator;

    private User $teacher;

    private GradeLevel $kelas1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = WorkUnit::create(['name' => 'SP Impor', 'code' => 'SCH-IMP', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create([
            'work_unit_id' => $this->unit->id,
            'npsn' => '20202020',
            'name' => 'SP Impor Halaqah',
            'school_gender' => 'putra',
        ]);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $this->kelas1 = GradeLevel::create(['school_id' => $this->school->id, 'level' => 1, 'name' => 'Kelas 1', 'is_active' => true]);

        $this->coordinator = $this->employee('Koordinator Impor', 'koord.imp@test.local', 'Guru Tahfidz');
        GtkAdditionalTask::create(['user_id' => $this->coordinator->id, 'nama_tugas' => 'Koordinator Guru Tahfidz']);
        $this->teacher = $this->employee('Guru Tahfidz Satu', 'guru.imp@test.local', 'Guru Tahfidz');
    }

    private function employee(string $name, string $email, string $jabatan): User
    {
        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jabatan' => $jabatan,
        ]);

        // Role dipasang setelah kepegawaian (observer GtkEmployment men-sync role).
        $user->assignRole(Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']));

        return $user;
    }

    private function student(string $name, string $nis): Student
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'nis' => $nis,
            'gender' => 'L',
            'status' => 'active',
        ]);

        $rombel = StudyGroup::firstOrCreate([
            'school_id' => $this->school->id,
            'grade_level_id' => $this->kelas1->id,
            'academic_year_id' => $this->year->id,
            'name' => '1A',
        ], ['is_active' => true]);

        StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $rombel->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);

        return $student;
    }

    private function service(): HalaqahImportService
    {
        return app(HalaqahImportService::class);
    }

    public function test_pratinjau_tidak_menulis_ke_database(): void
    {
        $this->student('Ahmad Fauzi', '1001');

        $plan = $this->service()
            ->read([
                ['nama_halaqah' => 'Halaqah Umar', 'kelas' => 'Kelas 1', 'guru' => $this->teacher->name, 'nis' => '1001', 'nama_santri' => ''],
            ], $this->school, $this->year->id)
            ->plan();

        $this->assertSame(1, $plan['totals']['baru']);
        $this->assertSame('Halaqah Umar', $plan['groups'][0]['name']);

        // Inti jaminan pratinjau: tidak ada yang tertulis.
        $this->assertSame(0, TahfidzGroup::count());
        $this->assertSame(0, TahfidzGroupMember::count());
    }

    public function test_simpan_membuat_halaqah_dan_anggota(): void
    {
        $student = $this->student('Ahmad Fauzi', '1001');

        $rows = [
            ['nama_halaqah' => 'Halaqah Umar', 'kelas' => 'Kelas 1', 'guru' => $this->teacher->name, 'nis' => '1001', 'nama_santri' => ''],
        ];

        $plan = $this->service()->read($rows, $this->school, $this->year->id)->plan();
        $result = $this->service()->read($rows, $this->school, $this->year->id)->commit($plan['groups'], $this->coordinator->id);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['members']);

        $group = TahfidzGroup::where('name', 'Halaqah Umar')->firstOrFail();
        $this->assertSame($this->kelas1->id, $group->grade_level_id);
        $this->assertSame($this->teacher->id, $group->teacher_id);
        $this->assertSame($this->coordinator->id, $group->coordinator_id);
        $this->assertSame('putra', $group->gender);

        $this->assertDatabaseHas('tahfidz_group_members', [
            'tahfidz_group_id' => $group->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);
    }

    public function test_nama_halaqah_sama_diperbarui_bukan_diduplikasi(): void
    {
        $this->student('Ahmad Fauzi', '1001');
        $this->student('Budi Santoso', '1002');

        $base = ['nama_halaqah' => 'Halaqah Umar', 'kelas' => 'Kelas 1', 'guru' => $this->teacher->name];

        $plan1 = $this->service()->read([$base + ['nis' => '1001', 'nama_santri' => '']], $this->school, $this->year->id)->plan();
        $this->service()->read([$base + ['nis' => '1001', 'nama_santri' => '']], $this->school, $this->year->id)->commit($plan1['groups']);

        // Unggah kedua, nama halaqah sama, anggota tambahan.
        $rows2 = [$base + ['nis' => '1002', 'nama_santri' => '']];
        $plan2 = $this->service()->read($rows2, $this->school, $this->year->id)->plan();

        $this->assertSame('update', $plan2['groups'][0]['action']);

        $result = $this->service()->read($rows2, $this->school, $this->year->id)->commit($plan2['groups']);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, TahfidzGroup::count(), 'Halaqah tidak boleh terduplikasi.');

        // Anggota lama TIDAK dikeluarkan — hanya menambah.
        $group = TahfidzGroup::first();
        $this->assertSame(2, TahfidzGroupMember::where('tahfidz_group_id', $group->id)->where('status', 'aktif')->count());
    }

    public function test_nis_kosong_dicari_lewat_nama_dan_nama_kembar_ditolak(): void
    {
        $this->student('Siti Aminah', '2001');
        $this->student('Kembar Sama', '2002');
        $this->student('Kembar Sama', '2003');

        $rows = [
            ['nama_halaqah' => 'Halaqah A', 'kelas' => 'Kelas 1', 'guru' => $this->teacher->name, 'nis' => '', 'nama_santri' => 'Siti Aminah'],
            ['nama_halaqah' => 'Halaqah A', 'kelas' => 'Kelas 1', 'guru' => $this->teacher->name, 'nis' => '', 'nama_santri' => 'Kembar Sama'],
        ];

        $plan = $this->service()->read($rows, $this->school, $this->year->id)->plan();

        // Satu anggota terdaftar; yang kembar menghasilkan error, bukan tebakan.
        $this->assertCount(1, $plan['groups'][0]['member_ids']);
        $this->assertSame(1, $plan['totals']['error']);
        $this->assertStringContainsString('Isi kolom NIS', $plan['errors'][0]['message']);
    }

    public function test_guru_bukan_guru_tahfidz_ditolak(): void
    {
        $other = $this->employee('Guru Matematika', 'mtk.imp@test.local', 'Guru Mata Pelajaran');

        $plan = $this->service()
            ->read([
                ['nama_halaqah' => 'Halaqah X', 'kelas' => 'Kelas 1', 'guru' => $other->name, 'nis' => '', 'nama_santri' => ''],
            ], $this->school, $this->year->id)
            ->plan();

        $this->assertSame([], $plan['groups']);
        $this->assertSame(1, $plan['totals']['error']);
        $this->assertStringContainsString('bukan Guru Tahfidz', $plan['errors'][0]['message']);
    }

    public function test_kelas_di_luar_satuan_pendidikan_ditolak(): void
    {
        $otherUnit = WorkUnit::create(['name' => 'SP Lain', 'code' => 'SCH-IMP2', 'type' => 'Unit Akademik', 'is_active' => true]);
        $otherSchool = School::create(['work_unit_id' => $otherUnit->id, 'npsn' => '21212121', 'name' => 'SP Lain', 'school_gender' => 'putra']);
        GradeLevel::create(['school_id' => $otherSchool->id, 'level' => 7, 'name' => 'Kelas 7', 'is_active' => true]);

        $plan = $this->service()
            ->read([
                ['nama_halaqah' => 'Halaqah Y', 'kelas' => 'Kelas 7', 'guru' => $this->teacher->name, 'nis' => '', 'nama_santri' => ''],
            ], $this->school, $this->year->id)
            ->plan();

        $this->assertSame(1, $plan['totals']['error']);
        $this->assertStringContainsString('tidak ada di satuan pendidikan ini', $plan['errors'][0]['message']);
    }

    public function test_halaman_impor_terbuka_untuk_koordinator(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah/import")
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Unggah Berkas', $html);
        $this->assertStringContainsString('tahfidz/halaqah/import/template', $html);

        // Halaman ini merender layout penuh yang meninggalkan buffer output
        // terbuka; tutup agar PHPUnit tidak menandai tes sebagai "risky".
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    public function test_halaman_impor_ditolak_untuk_guru_biasa(): void
    {
        $this->actingAs($this->teacher)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->teacher->id}/tahfidz/halaqah/import")
            ->assertForbidden();
    }
}
