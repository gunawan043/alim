<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\RaporDistributionSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Support\StudentNameFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengaturan format nama santri pada cetak rapor:
 * sesuai data, HURUF BESAR SEMUA, atau Kapital Di Awal Kata.
 */
class RaporNamaSantriTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Nama Rapor', 'code' => 'UNR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '33445566',
            'name' => 'Sekolah Nama Rapor',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
    }

    public function test_huruf_besar_semua(): void
    {
        $this->assertSame('MUHAMMAD ABDUL AZIZ', StudentNameFormatter::format('Muhammad Abdul Aziz', 'upper'));
        $this->assertSame('MUHAMMAD ABDUL AZIZ', StudentNameFormatter::format('muhammad abdul aziz', 'upper'));
    }

    public function test_kapital_di_awal_kata(): void
    {
        $this->assertSame('Muhammad Abdul Aziz', StudentNameFormatter::format('MUHAMMAD ABDUL AZIZ', 'title'));
        $this->assertSame('Muhammad Abdul Aziz', StudentNameFormatter::format('muhammad abdul aziz', 'title'));
    }

    public function test_kapital_di_awal_kata_menjaga_tanda_baca(): void
    {
        $this->assertSame('Ahmad Al-Faruq', StudentNameFormatter::format('AHMAD AL-FARUQ', 'title'));
        $this->assertSame("Siti Nur'Aini Binti 'Ali", StudentNameFormatter::format("SITI NUR'AINI BINTI 'ALI", 'title'));
    }

    public function test_sesuai_data_tidak_mengubah_apa_pun(): void
    {
        $this->assertSame('Muhammad bin Abdillah', StudentNameFormatter::format('Muhammad bin Abdillah', 'as_is'));
        $this->assertSame('MUHAMMAD', StudentNameFormatter::format('MUHAMMAD', null));
    }

    public function test_nama_arab_tidak_diubah(): void
    {
        $this->assertSame('أحمد محمد', StudentNameFormatter::format('أحمد محمد', 'upper'));
        $this->assertSame('أحمد محمد', StudentNameFormatter::format('أحمد محمد', 'title'));
    }

    public function test_nilai_tidak_dikenal_kembali_ke_sesuai_data(): void
    {
        $this->assertSame('as_is', StudentNameFormatter::normalize('aneh'));
        $this->assertSame('as_is', StudentNameFormatter::normalize(null));
        $this->assertSame('upper', StudentNameFormatter::normalize('UPPER'));
    }

    public function test_pengaturan_tersimpan_per_sekolah(): void
    {
        RaporDistributionSetting::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'student_name_case' => 'upper',
        ]);

        $setting = RaporDistributionSetting::forContext($this->school->id, $this->ay->id, 'ganjil');

        $this->assertSame('upper', $setting->layoutSettings()['student_name_case']);
        $this->assertSame('upper', $setting->variantLayoutSettings('sts', 'id')['student_name_case']);
        $this->assertSame('upper', $setting->variantLayoutSettings('sas', 'ar')['student_name_case']);
    }


    public function test_wakil_kepala_dapat_menyimpan_pengaturan_nama(): void
    {
        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7,
            'name' => 'Kelas VII', 'fase' => 'D', 'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7B', 'code' => '7B-NR', 'capacity' => 30, 'is_active' => true,
        ]);

        $wakil = User::create([
            'name' => 'Wakil Nama Rapor',
            'email' => 'wakil.nama@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $wakil->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Wakil Kepala Satuan Pendidikan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'margin_top' => 10, 'margin_right' => 15, 'margin_bottom' => 15, 'margin_left' => 15,
            'font_size' => 11, 'title_font_size' => 14, 'table_font_size' => 10,
            'info_font_size' => 10, 'footer_font_size' => 8, 'line_height' => 1.4,
            'paper_size' => 'a4',
            'doc_jenis' => 'sts',
            'doc_bahasa' => 'id',
            'student_name_case' => 'upper',
        ];

        $request = \Illuminate\Http\Request::create(
            route('user.schools.nilai-kelas.rapor.layout', ['userId' => $wakil->id, 'studyGroupId' => $group->id]),
            'POST',
            $payload
        );
        $request->setUserResolver(fn () => $wakil);
        $request->attributes->set('schoolContextId', $this->school->id);

        app(\App\Http\Controllers\NilaiKelasController::class)
            ->raporLayoutSettings($request, $wakil->id, $group->id);


        $setting = RaporDistributionSetting::forContext($this->school->id, $this->ay->id, 'ganjil');

        // Ditulis ke kolom dasar, bukan ke blob varian.
        $this->assertSame('upper', $setting->student_name_case);

        // Berlaku untuk semua varian, termasuk yang tidak ikut disimpan.
        $this->assertSame('upper', $setting->variantLayoutSettings('sas', 'ar')['student_name_case']);
    }

    public function test_bawaan_sesuai_data(): void
    {
        $this->assertSame('as_is', RaporDistributionSetting::defaultLayout()['student_name_case']);
    }

    public function test_teks_nama_pada_data_cetak_mengikuti_pengaturan(): void
    {
        $service = app(\App\Services\Arsip\RaporPdfService::class);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7,
            'name' => 'Kelas VII', 'fase' => 'D', 'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A', 'code' => '7A-NR', 'capacity' => 30, 'is_active' => true,
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'code' => 'BIND',
            'name' => 'Bahasa Indonesia',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '7777000011',
            'nis' => 'NR-01',
            'name' => 'muhammad abdul aziz',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $data = $service->buildSantriData(
            $this->school->id, $group, $this->ay, $this->ay->id, 'ganjil', 'sts', $student,
            null,
            'title',
        );

        $this->assertSame('Muhammad Abdul Aziz', $data[$student->id]['Nama']);
    }
}
