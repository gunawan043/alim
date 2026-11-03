<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\TahfidzTasmiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman referensi Tahfidz & Bahasa yang sebelumnya bertanda "Soon".
 *
 * Semua halaman di sini read-only: tidak ada tabel baru, hanya membaca
 * sumber yang sudah dipakai modul aslinya (TahfidzTasmiService,
 * tahfidz_surah_master, subject_kktp, language_violations).
 */
class ReferencePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private School $school;

    private string $academicYearId;

    private string $gradeLevelId;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();

        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Referensi',
            'code' => 'UREF'.random_int(100, 999),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '8'.random_int(1000000, 9999999),
            'name' => 'Sekolah Referensi',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $this->academicYearId = (string) Str::uuid();
        DB::table('academic_years')->insert([
            'id' => $this->academicYearId,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $this->gradeLevelId,
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::create([
            'name' => 'Kepala Referensi',
            'email' => 'kepala-ref-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->user->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->user->id,
            'school_id' => $this->school->id,
            'jabatan' => 'KEPALA SATUAN PENDIDIKAN',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function url(string $path): string
    {
        return '/'.$this->user->id.$path;
    }

    public function test_halaman_referensi_tahfidz_dan_bahasa_dapat_dirender(): void
    {
        foreach ([
            '/tahfidz/standar-penilaian',
            '/tahfidz/mapping-surah-juz',
            '/bahasa/standar-penilaian',
            '/bahasa/notifikasi-wali',
        ] as $path) {
            $this->actingAs($this->user)
                ->get($this->url($path))
                ->assertOk();
        }
    }

    public function test_skala_predikat_diturunkan_dari_service_bukan_ditulis_ulang(): void
    {
        $service = app(TahfidzTasmiService::class);

        $response = $this->actingAs($this->user)
            ->get($this->url('/tahfidz/standar-penilaian'))
            ->assertOk()
            ->assertSee('Mumtaz')
            ->assertSee('Rasib')
            ->assertSee('ممتاز', false);

        // Ambang batas di halaman harus sama persis dengan yang dipakai service.
        $this->assertSame('mumtaz', $service->predicate(90));
        $this->assertSame('jayyid_jiddan', $service->predicate(89));
        $this->assertSame('jayyid', $service->predicate(70));
        $this->assertSame('maqbul', $service->predicate(60));
        $this->assertSame('rasib', $service->predicate(59));

        $response->assertSee('90');
        $response->assertSee('59');
    }

    public function test_mapping_surah_menampilkan_surah_dari_master(): void
    {
        // id & nomor pada master Al-Qur'an memakai integer, bukan UUID.
        DB::table('tahfidz_juz_master')->insert([
            'id' => 30,
            'juz_number' => 30,
            'name' => 'Juz Amma',
            'halaman_start' => 582,
            'halaman_end' => 604,
            'total_halaman' => 23,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tahfidz_surah_master')->insert([
            [
                'id' => 78,
                'number' => 78,
                'name_arabic' => 'النبأ',
                'name_latin' => 'An-Naba',
                'juz' => 30,
                'total_ayat' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 79,
                'number' => 79,
                'name_arabic' => 'النازعات',
                'name_latin' => 'An-Nazi\'at',
                'juz' => 30,
                'total_ayat' => 46,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->user)
            ->get($this->url('/tahfidz/mapping-surah-juz'))
            ->assertOk()
            ->assertSee('Juz Amma')
            ->assertSee('An-Naba')
            ->assertSee('النبأ', false)
            ->assertSee('2 surah');
    }

    public function test_standar_penilaian_bahasa_menampilkan_kkm_dari_buku_ajar(): void
    {
        $subjectId = (string) Str::uuid();
        DB::table('subjects')->insert([
            'id' => $subjectId,
            'school_id' => $this->school->id,
            'code' => 'ARB-7',
            'name' => 'Bahasa Arab',
            'category' => 'nasional',
            'source' => 'sekolah',
            'credit_hours' => 2,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studyGroupId = (string) Str::uuid();
        DB::table('study_groups')->insert([
            'id' => $studyGroupId,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYearId,
            'grade_level_id' => $this->gradeLevelId,
            'name' => '7A',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kktpId = (string) Str::uuid();
        DB::table('subject_kktp')->insert([
            'id' => $kktpId,
            'subject_id' => $subjectId,
            'school_id' => $this->school->id,
            'grade_level_id' => $this->gradeLevelId,
            'academic_year_id' => $this->academicYearId,
            'semester' => 'ganjil',
            'kktp_score' => 78,
            'kkm_score' => 78,
            'notes' => 'Fokus mufradat pekanan',
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_admin_books')->insert([
            'id' => (string) Str::uuid(),
            'teacher_id' => $this->user->id,
            'subject_id' => $subjectId,
            'study_group_id' => $studyGroupId,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYearId,
            'semester' => 'ganjil',
            'kktp_id' => $kktpId,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get($this->url('/bahasa/standar-penilaian'))
            ->assertOk()
            ->assertSee('Bahasa Arab')
            ->assertSee('Fokus mufradat pekanan')
            ->assertSee('78')
            ->assertSee('Terisi');
    }

    public function test_notifikasi_wali_meringkas_pelanggaran_per_santri(): void
    {
        $studentId = (string) Str::uuid();
        DB::table('students')->insert([
            'id' => $studentId,
            'school_id' => $this->school->id,
            'name' => 'Santri Bahasa',
            'nis' => '26999',
            'gender' => 'L',
            'status' => 'active',
            'guardian_name' => 'Wali Santri',
            'mobile_phone' => '081200000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([['arabic_day', 5], ['english_day', 10]] as [$type, $points]) {
            DB::table('language_violations')->insert([
                'id' => (string) Str::uuid(),
                'school_id' => $this->school->id,
                'student_id' => $studentId,
                'violation_date' => now()->toDateString(),
                'violation_type' => $type,
                'points' => $points,
                'status' => 'recorded',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->user)
            ->get($this->url('/bahasa/notifikasi-wali'))
            ->assertOk()
            ->assertSee('Santri Bahasa')
            ->assertSee('2×')
            ->assertSee('15')
            ->assertSee('Tersedia');
    }

    public function test_notifikasi_wali_menolak_kirim_tanpa_santri_terpilih(): void
    {
        $this->actingAs($this->user)
            ->post($this->url('/bahasa/notifikasi-wali'), [
                'title' => 'Pemberitahuan',
                'message' => 'Mohon bimbingan.',
            ])
            ->assertSessionHasErrors('student_ids');
    }
}
