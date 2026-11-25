<?php

namespace Tests\Feature;

use App\Http\Controllers\NilaiKelasController;
use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\GradeLevel;
use App\Models\LegerPresensi;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Rekap Presensi S/I/A pada leger STS & SAS.
 *
 *  - Hitungan awal: S = hadir + terlambat, I = izin + sakit, A = alpa.
 *  - Isian manual di leger menimpa hitungan absensi harian per santri.
 */
class LegerPresensiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $studyGroup;

    private Student $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixture();
    }

    public function test_hitungan_awal_dari_absensi_harian(): void
    {
        $this->catatAbsensi(['hadir', 'hadir', 'terlambat', 'izin', 'sakit', 'alpa']);

        $map = $this->buildMap();

        $this->assertSame(3, $map[$this->santri->id]['s'], 'hadir + terlambat masuk S.');
        $this->assertSame(2, $map[$this->santri->id]['i'], 'izin + sakit masuk I.');
        $this->assertSame(1, $map[$this->santri->id]['a'], 'alpa masuk A.');
    }

    public function test_isian_manual_menimpa_hitungan_absensi(): void
    {
        $this->catatAbsensi(['hadir', 'izin']);
        $this->assertSame(1, $this->buildMap()[$this->santri->id]['s']);

        LegerPresensi::create([
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'student_id' => $this->santri->id,
            's' => 42,
            'i' => null,
            'a' => 7,
        ]);

        $map = $this->buildMap();

        $this->assertSame(42, $map[$this->santri->id]['s'], 'S manual menang.');
        $this->assertSame(1, $map[$this->santri->id]['i'], 'I null kembali ke absensi.');
        $this->assertSame(7, $map[$this->santri->id]['a'], 'A manual menang.');
    }

    public function test_tanpa_absensi_tetap_menampilkan_nol(): void
    {
        LegerPresensi::create([
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'student_id' => $this->santri->id,
            's' => 5,
        ]);

        $map = $this->buildMap();

        $this->assertSame(5, $map[$this->santri->id]['s']);
        $this->assertSame(0, $map[$this->santri->id]['i']);
        $this->assertSame(0, $map[$this->santri->id]['a']);
    }

    /**
     * Panggil helper privat controller tanpa melewati middleware otorisasi,
     * karena yang diuji aturan S/I/A-nya, bukan hak akses leger.
     */
    private function buildMap(): array
    {
        $method = new ReflectionMethod(NilaiKelasController::class, 'buildPresensiMap');
        $method->setAccessible(true);

        return $method->invoke(
            app(NilaiKelasController::class),
            $this->studyGroup->id,
            $this->ay->id,
            'ganjil'
        );
    }

    private function catatAbsensi(array $statuses): void
    {
        foreach ($statuses as $i => $status) {
            AdminPresensiHarian::create([
                'study_group_id' => $this->studyGroup->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'attendance_date' => now()->startOfMonth()->addDays($i)->toDateString(),
                'student_id' => $this->santri->id,
                'status' => $status,
            ]);
        }
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Presensi',
            'code' => 'UU-PRES',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77778888',
            'name' => 'Sekolah Uji Presensi',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->santri = Student::create([
            'school_id' => $this->school->id,
            'nis' => '0001',
            'name' => 'Santri Uji Presensi',
            'gender' => 'L',
            'is_active' => true,
        ]);

        StudentClassHistory::create([
            'student_id' => $this->santri->id,
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => true,
        ]);
    }
}
