<?php

namespace Tests\Feature;

use App\Http\Controllers\BulkPromotionController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NilaiKelasController;
use App\Http\Controllers\StudentController;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\NilaiSumatif;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ARSIP ROMBEL: seluruh data rombel per tahun ajaran wajib tersimpan.
 * - Anggota rombel TA lama tetap tampil di halaman santri per rombel & leger.
 * - Kenaikan kelas tidak menghapus buku mapel / nilai TA lama.
 */
class RombelArchiveTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ayOld;

    private AcademicYear $ayActive;

    private StudyGroup $rombelOld;

    private StudyGroup $rombelNew;

    private Student $student;

    private User $tu;

    private TeacherAdminBook $oldBook;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Arsip', 'code' => 'UUA',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '11122233',
            'name' => 'Sekolah Uji Arsip',
        ]);

        $this->ayOld = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $this->ayActive = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade1 = GradeLevel::create(['school_id' => $this->school->id, 'level' => 5, 'name' => 'Kelas 5', 'is_active' => true]);
        $grade2 = GradeLevel::create(['school_id' => $this->school->id, 'level' => 6, 'name' => 'Kelas 6', 'is_active' => true]);

        $this->rombelOld = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ayOld->id,
            'grade_level_id' => $grade1->id, 'name' => '5C', 'code' => '5C', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->rombelNew = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ayActive->id,
            'grade_level_id' => $grade2->id, 'name' => '6C', 'code' => '6C', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Arsip',
            'nisn' => '5556667770', 'gender' => 'P', 'status' => 'active',
        ]);

        // Histori TA lama (kelas 5C) — sudah nonaktif karena naik kelas.
        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->rombelOld->id,
            'academic_year_id' => $this->ayOld->id,
            'is_active' => false,
            'join_date' => '2025-07-01',
            'leave_date' => '2026-06-30',
            'attendance_number' => 1,
        ]);

        // Histori TA aktif (kelas 6C).
        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->rombelNew->id,
            'academic_year_id' => $this->ayActive->id,
            'is_active' => true,
            'join_date' => '2026-07-01',
            'attendance_number' => 1,
        ]);

        // Buku mapel + nilai pada TA LAMA — wajib tetap ada setelah kenaikan kelas.
        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika',
            'credit_hours' => 4, 'is_active' => true,
        ]);

        $this->oldBook = TeacherAdminBook::create([
            'teacher_id' => User::create([
                'name' => 'Guru Lama', 'email' => 'guru.lama@test.local',
                'password' => bcrypt('password'), 'is_active' => true,
            ])->id,
            'subject_id' => $subject->id,
            'study_group_id' => $this->rombelOld->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ayOld->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        NilaiSumatif::create([
            'admin_book_id' => $this->oldBook->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->ayOld->id,
            'semester' => 'ganjil',
            'sts' => 88,
        ]);

        $this->tu = User::create([
            'name' => 'TU Arsip', 'email' => 'tu.arsip@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->tu->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Staf Tata Usaha',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_arsip_rombel_lama_tetap_menampilkan_anggota(): void
    {
        $this->actingAs($this->tu);

        // Halaman santri per rombel: anggota TA lama (nonaktif) tetap tampil.
        $request = $this->makeRequest(['study_group_id' => $this->rombelOld->id]);
        $view = app(StudentController::class)->index($request);
        $students = $view->getData()['students'];

        $this->assertTrue(
            $students->contains(fn ($s) => $s->id === $this->student->id),
            'Anggota rombel TA lama harus tetap tampil di halaman santri per rombel (arsip).',
        );

        // Leger TA lama: anggota arsip tetap tampil.
        $legerRequest = $this->makeRequest([
            'academic_year_id' => $this->ayOld->id,
            'semester' => 'ganjil',
            'tab' => 'leger',
        ]);
        $legerView = app(NilaiKelasController::class)->sts($legerRequest, $this->tu->id, $this->rombelOld->id);
        $legerStudents = $legerView->getData()['students'];

        $this->assertTrue(
            $legerStudents->contains(fn ($h) => $h->student_id === $this->student->id),
            'Leger TA lama harus tetap menampilkan anggota rombel arsip.',
        );
    }

    public function test_tab_mapel_menampilkan_santri_nonaktif_dan_nilai_arsip(): void
    {
        $this->actingAs($this->tu);

        $request = $this->makeRequest([
            'academic_year_id' => $this->ayOld->id,
            'semester' => 'ganjil',
            'tab' => 'mapel',
            'admin_book_id' => $this->oldBook->id,
        ]);
        $this->app->instance('request', $request);

        $view = app(NilaiKelasController::class)->sts($request, $this->tu->id, $this->rombelOld->id);
        $html = $view->render();

        $this->assertStringContainsString('Santri Arsip', $html);
        $this->assertStringContainsString('Nonaktif', $html);
        $this->assertStringContainsString('data-inactive="1"', $html);
        $this->assertStringContainsString('value="88.00"', $html);
    }

    public function test_data_nilai_default_ta_aktif_dan_hanya_kelas_berpenghuni(): void
    {
        // Kelas aktif dengan buku + santri aktif.
        $activeBook = TeacherAdminBook::create([
            'teacher_id' => $this->oldBook->teacher_id,
            'subject_id' => $this->oldBook->subject_id,
            'study_group_id' => $this->rombelNew->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ayActive->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        // Kelas duplikat tanpa penghuni: tidak boleh tampil di Data Nilai.
        $emptyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ayActive->id,
            'grade_level_id' => $this->rombelNew->grade_level_id,
            'name' => '6Z',
            'code' => '6Z',
            'capacity' => 30,
            'is_active' => true,
        ]);
        TeacherAdminBook::create([
            'teacher_id' => $this->oldBook->teacher_id,
            'subject_id' => $this->oldBook->subject_id,
            'study_group_id' => $emptyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ayActive->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->actingAs($this->tu);
        $request = $this->makeRequest();
        $view = app(NilaiController::class)->index($request, $this->tu->id);
        $data = $view->getData();

        $this->assertSame($this->ayActive->id, $data['selectedAcademicYearId']);
        $this->assertSame('ganjil', $data['selectedSemester']);
        $this->assertTrue($data['kelasList']->contains(fn ($row) => $row['study_group']->id === $this->rombelNew->id));
        $this->assertFalse($data['kelasList']->contains(fn ($row) => $row['study_group']->id === $emptyGroup->id));
    }

    public function test_tab_mapel_ta_aktif_tanpa_riwayat_aktif_tetap_dapat_diisi(): void
    {
        // Simulasikan kelas yang seluruh riwayat TA aktifnya nonaktif (data migrasi),
        // tetapi buku dan nilainya sudah ada.
        $this->actingAs($this->tu);

        StudentClassHistory::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->ayActive->id)
            ->update(['is_active' => false, 'leave_date' => '2026-10-15']);

        $activeBook = TeacherAdminBook::create([
            'teacher_id' => $this->oldBook->teacher_id,
            'subject_id' => $this->oldBook->subject_id,
            'study_group_id' => $this->rombelNew->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ayActive->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $request = $this->makeRequest([
            'academic_year_id' => $this->ayActive->id,
            'semester' => 'ganjil',
            'tab' => 'mapel',
            'admin_book_id' => $activeBook->id,
        ]);
        $this->app->instance('request', $request);

        $view = app(NilaiKelasController::class)->sts($request, $this->tu->id, $this->rombelNew->id);
        $html = $view->render();

        $this->assertStringContainsString('data-inactive="0"', $html);
        $this->assertStringNotContainsString('Seluruh santri pada rombel ini berstatus', $html);
    }

    public function test_kenaikan_kelas_tidak_menghapus_buku_dan_nilai_ta_lama(): void
    {
        // Data TA lama lengkap sebelum promosi.
        $this->assertDatabaseHas('teacher_admin_books', ['id' => $this->oldBook->id, 'is_active' => 1]);
        $this->assertDatabaseHas('admin_nilai_sumatif', ['admin_book_id' => $this->oldBook->id, 'sts' => 88]);

        // Jalankan kenaikan kelas dari 5C (TA lama) ke 6C (TA baru, paralel).
        app(BulkPromotionController::class)->promote($this->makeRequest([
            'from_study_group_id' => $this->rombelOld->id,
            'to_academic_year_id' => $this->ayActive->id,
            'promotion_date' => '2026-07-01',
            'student_ids' => [$this->student->id],
        ]), $this->tu->id);

        // Buku & nilai TA LAMA tidak boleh berubah/hilang.
        $this->assertDatabaseHas('teacher_admin_books', ['id' => $this->oldBook->id, 'is_active' => 1]);
        $this->assertDatabaseHas('admin_nilai_sumatif', ['admin_book_id' => $this->oldBook->id, 'sts' => 88]);

        // Histori: TA lama tetap ada (nonaktif) + TA baru aktif → 2 baris.
        $this->assertSame(2, StudentClassHistory::where('student_id', $this->student->id)->count());
        $this->assertDatabaseHas('student_class_histories', [
            'student_id' => $this->student->id,
            'study_group_id' => $this->rombelOld->id,
            'academic_year_id' => $this->ayOld->id,
            'is_active' => 0,
        ]);
    }

    public function test_arsip_presensi_ta_lama_tetap_tampil_di_rekap(): void
    {
        // Catatan kehadiran pada TA lama (kelas 5C).
        \App\Models\AdminPresensiHarian::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->rombelOld->id,
            'academic_year_id' => $this->ayOld->id,
            'semester' => 'ganjil',
            'attendance_date' => '2025-09-01',
            'status' => 'hadir',
        ]);

        $this->actingAs($this->tu);

        $view = app(\App\Http\Controllers\AbsensiHarianController::class)->recapSemester(
            $this->makeRequest([
                'study_group_id' => $this->rombelOld->id,
                'academic_year_id' => $this->ayOld->id,
                'semester' => 'ganjil',
            ]),
            $this->tu->id,
        );

        $rekap = $view->getData()['rekapData'];
        $this->assertTrue(
            $rekap->contains(fn ($row) => ($row['name'] ?? null) === 'Santri Arsip'),
            'Rekap kehadiran TA lama harus menampilkan anggota rombel arsip.',
        );
    }

    private function makeRequest(array $data = []): Request
    {
        $request = Request::create('/rombel-arsip', 'GET', $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->tu);

        return $request;
    }
}
