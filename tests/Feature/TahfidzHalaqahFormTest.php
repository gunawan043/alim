<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkAdditionalTask;
use App\Models\GtkEmployment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\TahfidzGroup;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzHalaqahFormTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $unit;

    private School $school;

    private AcademicYear $year;

    private User $coordinator;

    private User $tahfidzTeacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = WorkUnit::create(['name' => 'SP Putra', 'code' => 'SCH-HQF', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create([
            'work_unit_id' => $this->unit->id,
            'npsn' => '17171717',
            'name' => 'SP Putra Halaqah',
            'school_gender' => 'putra',
        ]);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->coordinator = $this->employmentUser('Koordinator Halaqah', 'koord.hq@test.local', 'Guru Tahfidz');
        $this->tahfidzTeacher = $this->employmentUser('Guru Halaqah', 'guru.hq@test.local', 'Guru Tahfidz');

        // Tugas tambahan memakai kolom `nama_tugas` — sumber yang dulu salah dibaca.
        GtkAdditionalTask::create([
            'user_id' => $this->coordinator->id,
            'nama_tugas' => 'Koordinator Guru Tahfidz',
        ]);
    }

    private function employmentUser(string $name, string $email, string $jabatan): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jabatan' => $jabatan,
        ]);

        // Role dipasang SETELAH kepegawaian: observer GtkEmployment memanggil
        // syncRoles() dari data struktural, sehingga role yang dipasang lebih
        // dulu akan terhapus. Middleware EnsureEmployeeAccess menolak akun
        // tanpa Spatie role (redirect ke /auth/validator).
        $user->assignRole(Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']));

        return $user;
    }

    public function test_koordinator_dari_tugas_tambahan_dapat_membuat_halaqah(): void
    {
        $this->assertTrue(
            app(\App\Services\TahfidzAccess::class)->isSchoolCoordinator($this->coordinator, $this->school->id),
            'Koordinator dari tugas tambahan harus dikenali.'
        );

        $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah/create")
            ->assertOk()
            ->assertSee('Identitas Halaqah');
    }

    /**
     * Kasus nyata di produksi: koordinator sudah tercatat lewat tugas tambahan
     * tetapi tetap tidak bisa menambah halaqah.
     */
    public function test_variasi_nama_tugas_koordinator_dan_tanpa_tanggal_berakhir_dikenali(): void
    {
        $access = app(\App\Services\TahfidzAccess::class);

        foreach (['Koordinator Tahfidz', 'Koordinator Tahfiz', 'Koordinator Guru Tahfiz'] as $variasi) {
            $user = $this->employmentUser('Uji '.$variasi, str($variasi)->slug().'@test.local', 'Guru Tahfidz');
            GtkAdditionalTask::create(['user_id' => $user->id, 'nama_tugas' => $variasi]);

            $this->assertTrue(
                $access->isSchoolCoordinator($user->fresh(), $this->school->id),
                "Tugas tambahan \"{$variasi}\" harus dikenali sebagai koordinator."
            );
        }
    }

    public function test_tugas_tambahan_yang_sudah_berakhir_tidak_diakui(): void
    {
        $user = $this->employmentUser('Koordinator Lama', 'koord.lama@test.local', 'Guru Tahfidz');
        GtkAdditionalTask::create([
            'user_id' => $user->id,
            'nama_tugas' => 'Koordinator Guru Tahfidz',
            'tst' => now()->subYear()->toDateString(),
        ]);

        $this->assertFalse(
            app(\App\Services\TahfidzAccess::class)->isSchoolCoordinator($user->fresh(), $this->school->id),
            'Tugas tambahan yang sudah berakhir tidak boleh diakui.'
        );
    }

    public function test_form_hanya_menampilkan_field_yang_diperlukan(): void
    {
        $lvl = ob_get_level();
        $response = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah/create")
            ->assertOk();

        $html = $response->getContent();

        foreach (['name="tahfidz_package_id"', 'name="curriculum_id"', 'name="study_group_id"', 'name="room"', 'name="member_ids[]"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "Field {$forbidden} seharusnya tidak tampil.");
        }

        // Gender tetap tampil, tetapi terkunci (bukan pilihan).
        $this->assertStringContainsString('name="gender"', $html);
        $this->assertStringNotContainsString('<select name="gender"', $html);
    }

    public function test_koordinator_dan_gender_diisi_otomatis_saat_menyimpan(): void
    {
        $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->coordinator->id}/tahfidz/halaqah", [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'name' => 'Halaqah Otomatis',
                'teacher_id' => $this->tahfidzTeacher->id,
                // gender & coordinator_id sengaja TIDAK dikirim dari form.
            ])->assertRedirect();

        $group = TahfidzGroup::where('name', 'Halaqah Otomatis')->firstOrFail();

        $this->assertSame($this->coordinator->id, $group->coordinator_id);
        $this->assertSame('putra', $group->gender);
    }

    public function test_guru_non_tahfidz_tidak_muncul_di_pilihan(): void
    {
        $other = $this->employmentUser('Guru Matematika', 'mtk.hq@test.local', 'Guru Mata Pelajaran');

        $html = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah/create")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->tahfidzTeacher->name, $html);
        $this->assertStringNotContainsString($other->name, $html);
    }

    public function test_sekolah_lain_dipaksa_kembali_ke_satuan_pendidikan_pengguna(): void
    {
        $otherUnit = WorkUnit::create(['name' => 'SP Putri', 'code' => 'SCH-HQF2', 'type' => 'Unit Akademik', 'is_active' => true]);
        $otherSchool = School::create(['work_unit_id' => $otherUnit->id, 'npsn' => '18181818', 'name' => 'SP Putri Halaqah', 'school_gender' => 'putri']);

        $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->coordinator->id}/tahfidz/halaqah", [
                'school_id' => $otherSchool->id,
                'academic_year_id' => $this->year->id,
                'name' => 'Halaqah Luar',
                'teacher_id' => $this->tahfidzTeacher->id,
            ])->assertRedirect();

        $group = TahfidzGroup::where('name', 'Halaqah Luar')->firstOrFail();

        // Sekolah yang dikirim diabaikan: halaqah tetap milik satuan pendidikan pengguna,
        // dan gender mengikutinya (bukan gender sekolah yang dicoba dikirim).
        $this->assertSame($this->school->id, $group->school_id);
        $this->assertSame('putra', $group->gender);
    }

    public function test_kelas_hanya_dari_satuan_pendidikan_sendiri(): void
    {
        GradeLevel::create(['school_id' => $this->school->id, 'level' => 1, 'name' => 'Kelas 1', 'is_active' => true]);

        $otherUnit = WorkUnit::create(['name' => 'SP Lain', 'code' => 'SCH-HQF3', 'type' => 'Unit Akademik', 'is_active' => true]);
        $otherSchool = School::create(['work_unit_id' => $otherUnit->id, 'npsn' => '19191919', 'name' => 'SP Lain Halaqah', 'school_gender' => 'putra']);
        GradeLevel::create(['school_id' => $otherSchool->id, 'level' => 1, 'name' => 'Kelas 1 Lain', 'is_active' => true]);

        $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah/create")
            ->assertOk()
            ->assertSee('Kelas 1')
            ->assertDontSee('Kelas 1 Lain');
    }
}
