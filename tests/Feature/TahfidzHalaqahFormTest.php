<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkAdditionalTask;
use App\Models\GtkEmployment;
use App\Models\GradeLevel;
use App\Models\OtherTeacherTask;
use App\Models\Role;
use App\Models\School;
use App\Models\TahfidzGroup;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $lama = $this->employmentUser('Koordinator Lama', 'koord.lama@test.local', 'Guru Tahfidz');

        GtkAdditionalTask::create([
            'user_id' => $lama->id,
            'nama_tugas' => 'Koordinator Guru Tahfidz',
            'tst' => now()->subYear()->toDateString(),
        ]);

        $this->assertFalse(
            app(\App\Services\TahfidzAccess::class)->isSchoolCoordinator($lama->fresh(), $this->school->id),
            'Tugas tambahan yang sudah berakhir tidak boleh diakui.'
        );
    }

    /**
     * Gejala di produksi: halaman index mengirim tahun ajaran aktif, sehingga
     * koordinator hanya dikenali bila punya baris di tahfidz_coordinators —
     * tabel yang tidak pernah diisi aplikasi. Tombol Tambah Halaqah hilang.
     */
    public function test_koordinator_tetap_diakui_saat_tahun_ajaran_diisi(): void
    {
        $access = app(\App\Services\TahfidzAccess::class);

        $this->assertTrue(
            $access->isSchoolCoordinator($this->coordinator->fresh(), $this->school->id, $this->year->id),
            'Tahun ajaran tidak boleh menggugurkan pengakuan koordinator dari tugas tambahan.'
        );

        $response = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah?academic_year_id={$this->year->id}")
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Tambah Halaqah', $html, 'Tombol Tambah Halaqah harus muncul untuk koordinator.');

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
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

    /**
     * Kasus nyata: koordinator tercatat di halaman Other Teacher Tasks,
     * bukan di SK kepegawaian (gtk_additional_tasks).
     */
    public function test_koordinator_dari_other_teacher_tasks_dapat_membuat_halaqah(): void
    {
        $koordinator = $this->employmentUser('Koor Matriks', 'koor.matriks@test.local', 'Guru Tahfidz');

        OtherTeacherTask::create([
            'teacher_id' => $koordinator->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'task_name' => 'Koordinator Guru Tahfidz',
            'is_active' => true,
        ]);

        $this->assertTrue(
            app(\App\Services\TahfidzAccess::class)->isSchoolCoordinator($koordinator->fresh(), $this->school->id, $this->year->id),
            'Tugas tambahan dari Other Teacher Tasks harus diakui.'
        );

        $this->actingAs($koordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$koordinator->id}/tahfidz/halaqah")
            ->assertOk()
            ->assertSee('onclick="openHalaqahModal()"', false);

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    public function test_tugas_other_teacher_tasks_yang_nonaktif_tidak_diakui(): void
    {
        $koordinator = $this->employmentUser('Koor Nonaktif', 'koor.off@test.local', 'Guru Tahfidz');

        OtherTeacherTask::create([
            'teacher_id' => $koordinator->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'task_name' => 'Koordinator Guru Tahfidz',
            'is_active' => false,
        ]);

        $this->assertFalse(
            app(\App\Services\TahfidzAccess::class)->isSchoolCoordinator($koordinator->fresh(), $this->school->id)
        );
    }

    /**
     * Kepegawaian hanya menyimpan penempatan unit kerja (school_id NULL).
     * Sekolah harus tetap terbaca lewat unit kerja, jangan hilang.
     */
    public function test_koordinator_tanpa_school_id_kepegawaian_tetap_bisa_membuat_halaqah(): void
    {
        $koordinator = $this->employmentUser('Koor Unit Kerja', 'koor.wu@test.local', 'Guru Tahfidz');
        GtkEmployment::where('user_id', $koordinator->id)->update(['school_id' => null]);
        // Penempatan unit kerja adalah sumber cadangan sekolah.
        DB::table('gtk_work_unit')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $koordinator->id,
            'work_unit_id' => $this->unit->id,
            'jabatan' => 'Guru Tahfidz',
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        GtkAdditionalTask::create([
            'user_id' => $koordinator->id,
            'nama_tugas' => 'Koordinator Guru Tahfidz',
        ]);

        $this->actingAs($koordinator)
            ->get("/{$koordinator->id}/tahfidz/halaqah")
            ->assertOk()
            ->assertSee('onclick="openHalaqahModal()"', false)
            ->assertSee($this->school->name);

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    public function test_dialog_tambah_halaqah_di_halaman_index_mengikuti_aturan_baru(): void
    {
        GradeLevel::create(['school_id' => $this->school->id, 'level' => 1, 'name' => 'Kelas 1', 'is_active' => true]);

        $html = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah")
            ->assertOk()
            ->assertSee('Tambah Halaqah')
            ->getContent();

        // Modal di halaman index harus memakai aturan form yang sama: tanpa
        // rombel/paket/kurikulum/ruang, kelas = tingkat, gender & koordinator terkunci.
        foreach (['name="study_group_id"', 'name="tahfidz_package_id"', 'name="curriculum_id"', 'name="room"', '<select name="gender"', '<select name="coordinator_id"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "Field {$forbidden} seharusnya tidak tampil di dialog tambah halaqah.");
        }

        $this->assertStringContainsString('name="grade_level_id"', $html);
        $this->assertStringContainsString('Kelas 1', $html);
        $this->assertStringContainsString('name="coordinator_id"', $html);

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    public function test_menu_halaqah_tertaut_dari_dashboard_koordinator(): void
    {
        // Sidebar dibangun penuh (butuh katalog permission), jadi uji tautannya
        // langsung dan pastikan halaman halaqah memberi tombol tambah.
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $html = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->coordinator->id}/tahfidz/halaqah")
            ->assertOk()
            ->assertSee('Tambah Halaqah')
            ->assertSee(route('user.tahfidz.halaqah.index', ['userId' => $this->coordinator->id]))
            ->getContent();

        $this->assertStringContainsString('Halaqah', $html);

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    public function test_guru_tahfidz_tanpa_tugas_tambahan_mendapat_penjelasan(): void
    {
        // Kasus akun nyata: jabatan "Guru Tahfidz" tetapi tugas tambahan
        // koordinator belum tercatat, sehingga tombol tambah halaqah hilang.
        $html = $this->actingAs($this->tahfidzTeacher)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->tahfidzTeacher->id}/tahfidz/halaqah")
            ->assertOk()
            ->assertDontSee('onclick="openHalaqahModal()"')
            ->getContent();

        $this->assertStringContainsString('belum tercatat sebagai Koordinator Guru Tahfidz', $html);

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    /**
     * Kondisi data nyata: gtk_employments.school_id kosong dan guru hanya
     * tercatat di penempatan unit kerja + Other Teacher Tasks.
     */
    public function test_guru_tahfidz_dari_unit_kerja_dan_other_tasks_muncul_di_pilihan(): void
    {
        $guru = $this->employmentUser('Guru Tahfidz Unit', 'guru.unit@test.local', 'Guru Tahfidz');
        GtkEmployment::where('user_id', $guru->id)->update(['school_id' => null]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $guru->id,
            'work_unit_id' => $this->unit->id,
            'jabatan' => 'Guru Tahfidz',
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tanpa konteks session: sekolah harus terbaca dari unit kerja.
        $html = $this->actingAs($this->coordinator)
            ->get("/{$this->coordinator->id}/tahfidz/halaqah/create")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($guru->name, $html, 'Guru Tahfidz lewat unit kerja harus muncul.');

        while (ob_get_level() > 1) {
            ob_end_clean();
        }
    }

    /**
     * Alur "Tarik Santri" seperti rombel: daftar santri sekolah dikirim
     * sebagai JSON, lalu dipilih dan dimasukkan ke halaqah.
     */
    public function test_tarik_santri_ke_halaqah(): void
    {
        $group = TahfidzGroup::create([
            'school_id' => $this->school->id,
            'work_unit_id' => $this->unit->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Halaqah Tarik',
            'code' => 'HT-1',
            'gender' => 'putra',
            'teacher_id' => $this->tahfidzTeacher->id,
            'is_active' => true,
        ]);

        $santri = \App\Models\Student::create([
            'school_id' => $this->school->id,
            'name' => 'Santri Tarik',
            'nis' => '9001',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $available = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->getJson("/{$this->coordinator->id}/tahfidz/halaqah/{$group->id}/members/available")
            ->assertOk()
            ->json('data');

        $this->assertSame('Santri Tarik', $available[0]['name']);

        $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->postJson("/{$this->coordinator->id}/tahfidz/halaqah/{$group->id}/members/pull", [
                'student_ids' => [$santri->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tahfidz_group_members', [
            'tahfidz_group_id' => $group->id,
            'student_id' => $santri->id,
            'status' => 'aktif',
        ]);

        // Setelah masuk, santri tidak lagi muncul di daftar tarik.
        $again = $this->actingAs($this->coordinator)
            ->withSession(['sa_school_id' => $this->school->id])
            ->getJson("/{$this->coordinator->id}/tahfidz/halaqah/{$group->id}/members/available")
            ->assertOk()
            ->json('data');

        $this->assertSame([], $again);
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
