<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk penyempurnaan tingkat satuan pendidikan:
 *  - dropdown Dashboard tidak lagi menampilkan beberapa entri ke halaman yang sama
 *  - route debug tidak lagi tersedia
 *  - gating sidebar keamanan tetap membuka menu untuk role yang berhak
 */
class SatuanPendidikanSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $roleName, string $jabatan, ?string $principalOf = null): User
    {
        $workUnitId = (string) Str::uuid();

        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit '.$roleName,
            'code' => 'U'.substr(md5($roleName.$jabatan), 0, 4),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '7'.random_int(1000000, 9999999),
            'name' => 'Sekolah '.$roleName,
            'school_level' => 'SMP',
            'is_active' => true,
            'principal_user_id' => $principalOf,
        ]);

        $user = User::create([
            'name' => 'Uji '.$roleName,
            'email' => str(Str::slug($roleName)).'-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']));

        // Insert lewat query builder agar observer GtkEmployment tidak menimpa role.
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'jabatan' => $jabatan,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($principalOf === null) {
            $school->update(['principal_user_id' => $user->id]);
        }

        return $user->fresh();
    }

    public function test_dropdown_dashboard_tidak_menduplikasi_halaman_yang_sama(): void
    {
        // Kepala (via principal_user_id) yang juga menjabat Wakil → dua opsi
        // dashboard lama, padahal keduanya redirect ke halaman yang sama.
        $user = User::create([
            'name' => 'Uji Rangkap',
            'email' => 'rangkap-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->makeUser('Satuan Pendidikan', 'WAKIL KEPALA SATUAN PENDIDIKAN', $user->id);

        $user->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'jabatan' => 'WAKIL KEPALA SATUAN PENDIDIKAN',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user->fresh())
            ->get('/'.$user->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();

        // Tidak boleh ada lagi tautan ke route dashboard lama.
        $response->assertDontSee('/dashboard/kepala-satuan-pendidikan', false);
        $response->assertDontSee('/dashboard/wakil-kepala', false);

        // Hanya satu tautan menu menuju dashboard terpadu.
        preg_match_all(
            '/class="nav-link[^>]*href="[^"]*\/dashboard\/satuan-pendidikan"/s',
            $response->getContent(),
            $matches
        );

        $this->assertCount(
            1,
            $matches[0],
            'Dropdown Dashboard masih menampilkan lebih dari satu entri ke halaman yang sama.'
        );
    }

    public function test_sidebar_jabatan_memakai_section_yang_bisa_dilipat(): void
    {
        $user = $this->makeUser('Satuan Pendidikan', 'KEPALA SATUAN PENDIDIKAN');

        $response = $this->actingAs($user)
            ->get('/'.$user->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();

        // Section utama harus tersedia sebagai grup collapsible...
        foreach ([
            'sp_sec_rombel',
            'sp_sec_kesiswaan',
            'sp_sec_nilai',
            'sp_sec_akademik',
            'sp_sec_gtk',
            'sp_sec_sarpras',
            'sp_sec_administrasi',
            'sp_sec_kehadiran',
            'sp_sec_laporan',
            'sp_sec_lintas',
        ] as $sectionId) {
            $response->assertSee('id="'.$sectionId.'"', false);
        }

        // ...tanpa menghilangkan menu di dalamnya.
        $response->assertSee('Data Santri');
        $response->assertSee('Data Mahrom');
    }

    public function test_route_debug_sudah_dihapus(): void
    {
        $user = $this->makeUser('Satuan Pendidikan', 'KEPALA SATUAN PENDIDIKAN');

        $this->actingAs($user)
            ->get('/'.$user->id.'/route-test')
            ->assertNotFound();
    }

    public function test_sidebar_keamanan_tetap_tampil_untuk_role_satuan_keamanan(): void
    {
        $user = $this->makeUser('Satuan Keamanan', 'ANGGOTA SATUAN KEAMANAN');

        $response = $this->actingAs($user)
            ->get('/'.$user->id.'/keamanan');

        $response->assertOk();
        $response->assertSee('Pos Gerbang');
        $response->assertSee('Patroli & Ronda', false);
    }

    public function test_waka_yang_tetap_mengajar_mendapat_menu_guru_di_atas(): void
    {
        // Kasus nyata: menjabat Wakil Kepala tetapi jenis_gtk-nya "Tenaga
        // Kependidikan" — dulu menu guru hilang karena hanya membaca teks jabatan.
        $user = $this->makeUser('Satuan Pendidikan', 'WAKIL KEPALA SATUAN PENDIDIKAN');

        $schoolId = DB::table('gtk_employments')->where('user_id', $user->id)->value('school_id');
        $school = School::find($schoolId);

        $ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'code' => 'ENG',
            'name' => 'Bahasa Inggris',
            'is_active' => true,
        ]);

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/SIDEBAR/'.Str::random(6),
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji Sidebar)',
            'academic_year_id' => $ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $user->id,
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $group->id,
            'subject_id' => $subject->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user->fresh())
            ->get('/'.$user->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();

        // Menu guru harus muncul walau jabatannya struktural...
        // Label topiknya "Mengajar · ..." sejak menu guru dipecah per topik.
        $response->assertSee('Mengajar ·');
        $response->assertSee('id="sp_sec_guru_absensi"', false);
        $response->assertSee('id="sp_sec_guru_kurikulum"', false);
        $response->assertSee('id="sp_sec_guru_nilai"', false);
        $response->assertSee('id="sp_sec_guru_jurnal"', false);
        $response->assertSee('Jadwal Mengajar Saya');
        $response->assertSee('Buku Nilai Saya');
        $response->assertSee('Jurnal Pembelajaran');

        // ...dan berada di atas section Manajemen Sekolah (Rombel/Kesiswaan/Nilai).
        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'sp_sec_rombel'),
            strpos($html, 'sp_sec_guru_absensi'),
            'Menu guru harus tampil sebelum menu manajemen.'
        );
    }

    public function test_menu_guru_tahfidz_menaruh_menu_harian_di_atas_tanpa_grup(): void
    {
        $user = $this->makeUser('Satuan Pendidikan', 'GURU TAHFIDZ');

        // makeUser() menjadikan user kepala sekolah; lepaskan agar guru tahfidz
        // murni yang diuji, bukan pemegang jabatan struktural.
        School::where('principal_user_id', $user->id)->update(['principal_user_id' => null]);

        $response = $this->actingAs($user->fresh())
            ->get('/'.$user->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();

        // Menu guru tampil sebagai topik "Mengajar · ...", bukan "Menu Guru".
        $response->assertSee('Mengajar · Guru');

        // Menu harian berdiri sendiri — tidak dibungkus grup Tahfidz lagi.
        foreach (['Absensi Halaqah', 'Input Setoran Harian', 'Lembar Monitoring', 'Buku Nilai'] as $label) {
            $response->assertSee($label);
        }
        $response->assertDontSee('id="sp_sec_guru_tahfidz"', false);

        // Menu pendukung dikelompokkan per fungsi.
        $response->assertSee('id="sp_sec_guru_tahfidz_santri"', false);
        $response->assertSee('id="sp_sec_guru_tahfidz_nilai"', false);

        // Menu harian harus muncul sebelum grup pendukungnya.
        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'sp_sec_guru_tahfidz_santri'),
            strpos($html, 'Input Setoran Harian'),
            'Input Setoran Harian harus tampil sebelum grup Santri & Target.'
        );
        $this->assertLessThan(
            strpos($html, 'sp_sec_guru_tahfidz_nilai'),
            strpos($html, 'Lembar Monitoring'),
            'Lembar Monitoring harus tampil sebelum grup Nilai & Ujian.'
        );

        // Tidak ada tautan ganda untuk menu harian.
        $this->assertSame(1, substr_count($html, '>Input Setoran Harian<'));
    }
}
