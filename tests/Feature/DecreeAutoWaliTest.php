<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\JenisGtk;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\AdditionalTaskTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Wali Kelas otomatis SD IT kelas 1–3.
 *
 * Aturan dikirim server sebagai data (jenjang + tingkat), bukan sebagai hasil
 * yang sudah jadi, sehingga jenjang lain tidak pernah mendapat aturan ini.
 */
class DecreeAutoWaliTest extends TestCase
{
    use RefreshDatabase;

    private function sekolah(string $level): School
    {
        $unit = WorkUnit::create([
            'name' => "Unit {$level}", 'code' => 'U-'.$level, 'type' => 'Unit Akademik', 'is_active' => true,
        ]);

        return School::create([
            'work_unit_id' => $unit->id, 'npsn' => '9090'.$level, 'name' => "Sekolah {$level}",
            'school_level' => $level, 'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Auto', 'email' => 'admin.'.Str::random(6).'@test.local',
            'password' => bcrypt('x'), 'is_active' => true, 'is_system_admin' => true,
        ]);
    }

    private function tahunAjaran(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
    }

    private function halamanSekolah(string $level): string
    {
        $school = $this->sekolah($level);
        $year = $this->tahunAjaran();
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(
            '/'.$admin->id.'/institution-decrees/create?school_id='.$school->id.'&academic_year_id='.$year->id
        );

        $response->assertOk();

        return $response->getContent();
    }

    private function aturanDari(string $html): array
    {
        $this->assertSame(1, preg_match('/const autoWaliSubjects\s*=\s*(\[.*?\]);/s', $html, $m), 'Konstanta autoWaliSubjects tidak ditemukan.');

        return json_decode($m[1], true) ?: [];
    }

    public function test_sd_it_kelas_1_sampai_3_mendapat_aturan_mapel_otomatis(): void
    {
        $aturan = $this->aturanDari($this->halamanSekolah('sd'));

        $this->assertSame([1, 2, 3], array_column($aturan, 'level'));

        $perLevel = collect($aturan)->keyBy('level');

        foreach ([1, 2] as $level) {
            $this->assertSame(
                ['Matematika', 'Seni Budaya', 'Bahasa Indonesia', 'PJOK'],
                $perLevel[$level]['subjects']
            );
        }

        $this->assertSame(
            ['Matematika', 'Seni Budaya', 'Bahasa Indonesia', 'IPAS', 'PJOK'],
            $perLevel[3]['subjects']
        );
    }

    public function test_jenjang_smp_tidak_mendapat_aturan_otomatis(): void
    {
        $this->assertSame([], $this->aturanDari($this->halamanSekolah('smp')));
    }

    public function test_jenjang_sma_tidak_mendapat_aturan_otomatis(): void
    {
        $this->assertSame([], $this->aturanDari($this->halamanSekolah('sma')));
    }

    public function test_aturan_tidak_memuat_kelas_4_sampai_6(): void
    {
        // Wali Kelas di atas kelas 3 harus tetap seperti sekarang (tanpa tambahan).
        $levels = array_column($this->aturanDari($this->halamanSekolah('sd')), 'level');

        foreach ([4, 5, 6] as $level) {
            $this->assertNotContains($level, $levels, "Kelas {$level} tidak boleh mendapat aturan otomatis.");
        }
    }

    public function test_halaman_memuat_fungsi_otomatisasi_wali_kelas(): void
    {
        $html = $this->halamanSekolah('sd');

        foreach (['applyAutoWali', 'removeAutoWali', 'autoSubjectFor', 'alignedTaskRows', 'markAutoPendamping'] as $fn) {
            $this->assertStringContainsString("function {$fn}", $html, "Fungsi {$fn} tidak ada di halaman SK.");
        }
    }

    public function test_mapel_master_dicocokkan_lewat_nama_dan_kode(): void
    {
        // Mapel master tidak selalu memakai nama panjang; kode singkat harus
        // tetap dikenali agar otomatisasi tidak dilewati.
        $school = $this->sekolah('sd');
        $year = $this->tahunAjaran();

        foreach ([['Matematika', 'MTK'], ['Seni Budaya', 'SBdP'], ['Bahasa Indonesia', 'BIN'], ['PJOK', 'PJOK'], ['IPAS', 'IPAS']] as [$nama, $kode]) {
            Subject::create([
                'school_id' => $school->id, 'name' => $nama, 'code' => $kode, 'is_active' => true,
            ]);
        }

        $admin = $this->admin();
        $html = $this->actingAs($admin)
            ->get('/'.$admin->id.'/institution-decrees/create?school_id='.$school->id.'&academic_year_id='.$year->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('const subjects', $html);
        $this->assertSame(3, count($this->aturanDari($html)));
    }

    public function test_sd_dengan_tingkat_2_memuat_rombel_untuk_pendamping(): void
    {
        // Otomatisasi butuh rombel lain pada tingkat yang sama. Pastikan halaman
        // mengirim daftar rombel beserta gradeLevelId-nya.
        $school = $this->sekolah('sd');
        $year = $this->tahunAjaran();
        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 2, 'name' => 'Kelas 2', 'fase' => 'A', 'is_active' => true,
        ]);

        foreach (['2A', '2B', '2C'] as $nama) {
            StudyGroup::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'grade_level_id' => $grade->id, 'name' => $nama, 'code' => $nama, 'is_active' => true,
            ]);
        }

        $sub = Subject::create(['school_id' => $school->id, 'name' => 'Matematika', 'code' => 'MTK', 'is_active' => true]);
        GradeLevelSubject::create([
            'academic_year_id' => $year->id, 'grade_level_id' => $grade->id,
            'subject_id' => $sub->id, 'allocation_hours' => 5, 'is_active' => true,
        ]);

        $admin = $this->admin();
        $html = $this->actingAs($admin)
            ->get('/'.$admin->id.'/institution-decrees/create?school_id='.$school->id.'&academic_year_id='.$year->id)
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/const studyGroups\s*=\s*(\[.*?\]);/s', $html, $m));
        $groups = json_decode($m[1], true);

        $this->assertCount(3, $groups);
        foreach ($groups as $group) {
            $this->assertSame((string) $grade->id, (string) $group['gradeLevelId'], 'gradeLevelId rombel wajib ada.');
        }

        // JP per tingkat dipakai otomatisasi untuk menghitung jam mapel.
        $this->assertStringContainsString('const pendampingHoursByGrade', $html);
    }

    public function test_jenis_gtk_satuan_pendidikan_tersedia_untuk_tugas_tambahan(): void
    {
        JenisGtk::create(['id' => (string) Str::uuid(), 'nama' => 'Satuan Pendidikan', 'is_active' => true]);
        $this->seed(AdditionalTaskTypeSeeder::class);

        $html = $this->halamanSekolah('sd');

        $this->assertStringContainsString('Guru Pendamping', $html);
        $this->assertStringContainsString('Wali Kelas', $html);
    }
}
