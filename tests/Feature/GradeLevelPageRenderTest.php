<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman Tingkat Kelas harus menampilkan kategori baru (Pondok/Dinas),
 * jurusan rombel, dan menghormati penyaring tahun ajaran.
 */
class GradeLevelPageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_tingkat_menampilkan_kategori_jurusan_dan_arsip(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Halaman', 'code' => 'UHL',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '11223344',
            'name' => 'Sekolah Halaman', 'school_level' => 'sma', 'is_active' => true,
        ]);

        $old = AcademicYear::create([
            'name' => '2024/2025', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2024-07-01', 'end_date' => '2024-12-31',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 11, 'name' => 'Kelas 11', 'code' => '11',
        ]);

        $pondok = Subject::create([
            'school_id' => $school->id, 'code' => 'FQH', 'name' => 'Fiqih',
            'category' => 'pondok', 'credit_hours' => 3,
        ]);
        $dinas = Subject::create([
            'school_id' => $school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'dinas', 'credit_hours' => 4,
        ]);

        GradeLevelSubject::create([
            'grade_level_id' => $grade->id, 'academic_year_id' => $year->id,
            'subject_id' => $pondok->id, 'allocation_hours' => 3,
            'subject_source' => 'pondok', 'sort_order' => 1, 'track' => null, 'is_active' => true,
        ]);
        GradeLevelSubject::create([
            'grade_level_id' => $grade->id, 'academic_year_id' => $old->id,
            'subject_id' => $dinas->id, 'allocation_hours' => 4,
            'subject_source' => 'dinas', 'sort_order' => 2, 'track' => null, 'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Admin Halaman', 'email' => 'admin.halaman@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $url = route('user.grade-levels.show', ['userId' => $user->id, 'id' => $grade->id]);

        // TA aktif: hanya mapel periode ini, kategori & jurusan tampil.
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertSee('Fiqih')
            ->assertDontSee('<td><span class="fw-semibold" style="font-size:0.88rem">Matematika</span></td>', false)
            ->assertSee('Pondok');
        $this->actingAs($user)->get($url)->assertSee('Rombel &amp; Jurusan', false)
            ->assertDontSee('Tahun ajaran aktif — data lama');

        // TA lama: mapel periode itu tampil dan ditandai arsip.
        $this->actingAs($user)->get($url.'?ay_filter='.$old->id)
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Dinas')
            ->assertSee('>Arsip', false);
    }

    public function test_ubah_jurusan_rombel_dari_halaman_tingkat(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Ubah', 'code' => 'UBH',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '55667788',
            'name' => 'Sekolah Ubah', 'school_level' => 'sma', 'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 11, 'name' => 'Kelas 11', 'code' => '11',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'BA', 'name' => 'Bahasa Arab Lanjut',
            'category' => 'pondok', 'credit_hours' => 4,
        ]);
        GradeLevelSubject::create([
            'grade_level_id' => $grade->id, 'academic_year_id' => $year->id,
            'subject_id' => $subject->id, 'allocation_hours' => 4,
            'subject_source' => 'pondok', 'track' => 'bahasa', 'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '11A', 'code' => '11A',
            'capacity' => 30, 'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Admin Ubah', 'email' => 'admin.ubah@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->actingAs($user)->put(route('user.grade-levels.rombels.peminatan', [
            'userId' => $user->id, 'id' => $grade->id, 'groupId' => $group->id,
        ]), ['peminatan' => 'bahasa'])->assertRedirect();

        $this->assertSame('bahasa', $group->fresh()->peminatan);

        $this->actingAs($user)->get(route('user.grade-levels.show', ['userId' => $user->id, 'id' => $grade->id]))
            ->assertOk()
            ->assertSee('Bahasa');
    }

    /**
     * Kolom dan pilihan jurusan hanya muncul di Kelas 11 & 12.
     */
    public function test_kolom_jurusan_hanya_untuk_kelas_11_dan_12(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Jenjang', 'code' => 'UJN',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '99887766',
            'name' => 'Sekolah Jenjang', 'school_level' => 'sma', 'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'FQ', 'name' => 'Fiqih',
            'category' => 'pondok', 'credit_hours' => 3,
        ]);

        $user = User::create([
            'name' => 'Admin Jenjang', 'email' => 'admin.jenjang@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $buatTingkat = function (int $level) use ($school, $year, $subject) {
            $grade = GradeLevel::create([
                'school_id' => $school->id, 'level' => $level,
                'name' => 'Kelas '.$level, 'code' => (string) $level,
            ]);
            GradeLevelSubject::create([
                'grade_level_id' => $grade->id, 'academic_year_id' => $year->id,
                'subject_id' => $subject->id, 'allocation_hours' => 3,
                'subject_source' => 'pondok', 'is_active' => true,
            ]);

            return $grade;
        };

        // Kelas 10: tidak ada kolom jurusan, tidak ada pilihan jurusan.
        $kelas10 = $buatTingkat(10);
        $this->actingAs($user)->get(route('user.grade-levels.show', ['userId' => $user->id, 'id' => $kelas10->id]))
            ->assertOk()
            ->assertDontSee('name="track"', false)
            ->assertDontSee('Rombel &amp; Jurusan', false)
            ->assertSee('<th class="text-center text-uppercase">Urutan</th>', false);

        // Kelas 11: kolom jurusan ada.
        $kelas11 = $buatTingkat(11);
        $this->actingAs($user)->get(route('user.grade-levels.show', ['userId' => $user->id, 'id' => $kelas11->id]))
            ->assertOk()
            ->assertSee('name="track"', false)
            ->assertSee('<th class="text-center text-uppercase">Jurusan</th>', false)
            ->assertSee('<th class="text-center text-uppercase">Urutan</th>', false);
    }

    public function test_jurusan_ditolak_untuk_kelas_di_bawah_11(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Tolak', 'code' => 'UTL',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '44332211',
            'name' => 'Sekolah Tolak', 'school_level' => 'sma', 'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 10, 'name' => 'Kelas 10', 'code' => '10',
        ]);

        $group = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '10A', 'code' => '10A',
            'capacity' => 30, 'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Admin Tolak', 'email' => 'admin.tolak@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->actingAs($user)->put(route('user.grade-levels.rombels.peminatan', [
            'userId' => $user->id, 'id' => $grade->id, 'groupId' => $group->id,
        ]), ['peminatan' => 'ipa'])->assertStatus(422);

        $this->assertNull($group->fresh()->peminatan);
    }

    public function test_kolom_jurusan_peta_tersembunyi_untuk_kelas_10(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Peta', 'code' => 'UPT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '12340009',
            'name' => 'Sekolah Peta', 'school_level' => 'sma', 'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'AQ', 'name' => 'Aqidah',
            'category' => 'pondok', 'credit_hours' => 2,
        ]);

        $user = User::create([
            'name' => 'Admin Peta', 'email' => 'admin.peta@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $buat = function (int $level) use ($school, $year, $subject) {
            $grade = GradeLevel::create([
                'school_id' => $school->id, 'level' => $level,
                'name' => 'Kelas '.$level, 'code' => (string) $level,
            ]);
            GradeLevelSubject::create([
                'grade_level_id' => $grade->id, 'academic_year_id' => $year->id,
                'subject_id' => $subject->id, 'allocation_hours' => 2,
                'subject_source' => 'pondok', 'is_active' => true,
            ]);

            return $grade;
        };

        $kelas10 = $buat(10);
        $kelas11 = $buat(11);

        $peta = route('user.kurikulum.index', ['userId' => $user->id, 'academic_year_id' => $year->id]);

        // Hanya Kelas 10: kolom Jurusan tidak dirender sama sekali.
        $this->actingAs($user)->get($peta.'&grade_level_id='.$kelas10->id)
            ->assertOk()
            // Skrip halaman tetap memuat atribut ini; yang diperiksa adalah perenderan kolomnya.
            ->assertDontSee('>Jurusan</th>', false)
            ->assertDontSee('<span class="text-muted" title="Penjurusan hanya untuk Kelas 11 dan 12">', false);

        // Hanya Kelas 11: kolom Jurusan muncul.
        $this->actingAs($user)->get($peta.'&grade_level_id='.$kelas11->id)
            ->assertOk()
            ->assertSee('>Jurusan</th>', false);
    }
}
