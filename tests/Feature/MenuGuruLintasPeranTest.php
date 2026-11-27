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
use App\Support\TeacherWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Menu Guru mengikuti SK mengajar, bukan role atau penempatan kerja.
 *
 * Kasus nyata: Kepala Asrama yang juga mengampu mapel, dan GTK yang
 * ditempatkan di luar satuan pendidikan tetapi mengajar di satu sekolah.
 * Keduanya harus mendapat Menu Guru beserta konteks sekolah yang benar,
 * sedangkan GTK tanpa tugas mengajar tidak.
 */
class MenuGuruLintasPeranTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ay;

    private School $school;

    private StudyGroup $group;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'SMP IT Uji',
            'code' => 'SMP-UJI',
            'type' => 'Unit Akademik',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '70000001',
            'name' => 'SMP IT Uji',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'AQD',
            'name' => 'Aqidah',
            'is_active' => true,
        ]);
    }

    private function makeUser(string $roleName, string $jabatan, ?string $placementUnitId = null): User
    {
        $user = User::create([
            'name' => 'Uji '.$roleName,
            'email' => str(Str::slug($roleName)).'-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'jabatan' => $jabatan,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($placementUnitId) {
            DB::table('gtk_work_unit')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'work_unit_id' => $placementUnitId,
                'jabatan' => $jabatan,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user->fresh();
    }

    private function placeInDormitory(): string
    {
        $workUnitId = (string) Str::uuid();

        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Asrama SMP IT Uji',
            'code' => 'ASR-UJI',
            'type' => 'Unit Penunjang Akademik',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $workUnitId;
    }

    private function assignTeaching(User $user): void
    {
        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/MENUGURU/'.Str::random(6),
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji Menu Guru)',
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $user->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->subject->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);
    }

    public function test_kepala_asrama_yang_mengajar_mendapat_menu_guru(): void
    {
        $dormUnitId = $this->placeInDormitory();
        $user = $this->makeUser('Asrama', 'Kepala Asrama', $dormUnitId);

        DB::table('dormitories')->insert([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $dormUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-01',
            'name' => 'Asrama Uji',
            'gender' => 'putri',
            'head_id' => $user->id,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assignTeaching($user);

        $response = $this->actingAs($user)->get('/'.$user->id.'/dashboard/asrama');

        $response->assertOk();
        $response->assertSee('Mengajar · Guru Mapel');
        $response->assertSee('Jadwal Mengajar Saya');
        $response->assertSee('Buku Nilai Saya');
        $response->assertSee('Scan QR Kehadiran');
    }

    public function test_gtk_di_luar_satuan_pendidikan_mendapat_menu_guru_dan_konteks_sekolah(): void
    {
        $dormUnitId = $this->placeInDormitory();
        $user = $this->makeUser('Asrama', 'Wakil Kepala Asrama', $dormUnitId);

        // Penempatan kerja di asrama tidak punya sekolah; satunya-satunya bukti
        // adalah SK mengajar.
        $this->assertNull(\App\Services\SchoolGroupService::getUserSchool($user));

        $this->assignTeaching($user);

        $this->assertSame($this->school->id, TeacherWorkspace::preferredSchoolId($user->id));

        $response = $this->actingAs($user)->get('/'.$user->id.'/dashboard/asrama');

        $response->assertOk();
        $response->assertSee('Mengajar · Guru Mapel');
        $response->assertSee('Buku Nilai Saya');
        $response->assertSee('SMP IT Uji');
    }

    public function test_gtk_tanpa_tugas_mengajar_tidak_mendapat_menu_guru(): void
    {
        $dormUnitId = $this->placeInDormitory();
        $user = $this->makeUser('Asrama', 'Staf Asrama', $dormUnitId);

        $response = $this->actingAs($user)->get('/'.$user->id.'/dashboard/asrama');

        $response->assertOk();
        $response->assertDontSee('Mengajar · Guru Mapel');
        $response->assertDontSee('Jadwal Mengajar Saya');
    }

    public function test_sk_mengajar_tidak_aktif_tidak_membuka_menu_guru(): void
    {
        $dormUnitId = $this->placeInDormitory();
        $user = $this->makeUser('Asrama', 'Kepala Asrama', $dormUnitId);

        $this->assignTeaching($user);
        TeachingAssignment::query()->where('teacher_id', $user->id)->update(['status' => 'inactive']);
        // Buku nilai yang tertinggal dari SK lama juga tidak boleh dihitung.
        DB::table('teacher_admin_books')->where('teacher_id', $user->id)->update(['is_active' => false]);

        $this->assertFalse(TeacherWorkspace::hasTeachingRole($user->id));

        $response = $this->actingAs($user)->get('/'.$user->id.'/dashboard/asrama');

        $response->assertOk();
        $response->assertDontSee('Mengajar · Guru Mapel');
    }

    public function test_absensi_kelas_membuka_rombel_yang_diampu_bukan_seluruh_sekolah(): void
    {
        $dormUnitId = $this->placeInDormitory();
        $user = $this->makeUser('Asrama', 'Kepala Asrama', $dormUnitId);
        $this->assignTeaching($user);

        // Rombel lain di sekolah yang sama tidak boleh ikut terbuka.
        $other = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->group->grade_level_id,
            'name' => '9Z',
            'code' => '9Z',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/'.$user->id.'/absensi/harian');

        $response->assertOk();
        $response->assertSee('7A');
        $response->assertDontSee($other->name);
    }

    public function test_guru_tanpa_sk_mengajar_tetap_ditolak_di_absensi_kelas(): void
    {
        $dormUnitId = $this->placeInDormitory();
        $user = $this->makeUser('Asrama', 'Staf Asrama', $dormUnitId);

        $this->actingAs($user)
            ->get('/'.$user->id.'/absensi/harian')
            ->assertForbidden();
    }
}
