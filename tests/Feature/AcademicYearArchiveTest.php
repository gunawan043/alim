<?php

namespace Tests\Feature;

use App\Http\Controllers\Kurikulum\KurikulumController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NilaiKelasController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudyGroupController;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\NilaiSumatif;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ARSIP TAHUN AJARAN:
 * - Data Nilai dapat dipilih per TA/semester; TA lama tetap terbaca sebagai arsip.
 * - Peta Kurikulum tersimpan per periode + mekanisme siapkan TA baru.
 * - Data Santri memiliki filter TA + kolom Rombel yang mengikuti periode.
 * - Data Rombel menyediakan akses cepat santri pada periode rombel.
 * - Sidebar hanya menampilkan rombel TA aktif.
 */
class AcademicYearArchiveTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $oldAy;

    private AcademicYear $activeAy;

    private AcademicYear $targetAy;

    private GradeLevel $gradeOld;

    private GradeLevel $gradeActive;

    private Subject $subject;

    private StudyGroup $oldRombel;

    private StudyGroup $activeRombel;

    private Student $arsipStudent;

    private Student $activeStudent;

    private User $tu;

    private User $editor;

    private TeacherAdminBook $oldBook;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Arsip TA', 'code' => 'UUA-AY',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99988877',
            'name' => 'Sekolah Uji Arsip TA',
        ]);

        $this->oldAy = AcademicYear::create([
            'name' => '2024/2025', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
        ]);

        $this->activeAy = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->targetAy = AcademicYear::create([
            'name' => '2027/2028', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2027-07-01', 'end_date' => '2028-06-30',
        ]);

        $this->gradeOld = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 5, 'name' => 'Kelas 5', 'is_active' => true,
        ]);
        $this->gradeActive = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 6, 'name' => 'Kelas 6', 'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika',
            'credit_hours' => 4, 'is_active' => true,
        ]);

        $this->oldRombel = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->oldAy->id,
            'grade_level_id' => $this->gradeOld->id, 'name' => '6B-ARSIP', 'code' => '6B',
            'capacity' => 30, 'is_active' => true,
        ]);
        $this->activeRombel = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->activeAy->id,
            'grade_level_id' => $this->gradeActive->id, 'name' => '7A-AKTIF', 'code' => '7A',
            'capacity' => 30, 'is_active' => true,
        ]);

        $this->arsipStudent = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Arsip Lama',
            'nisn' => '8880001111', 'gender' => 'P', 'status' => 'active',
        ]);
        $this->activeStudent = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Aktif Baru',
            'nisn' => '8880001112', 'gender' => 'L', 'status' => 'active',
        ]);

        // Penempatan TA lama (sudah berakhir — naik kelas).
        StudentClassHistory::create([
            'student_id' => $this->arsipStudent->id,
            'study_group_id' => $this->oldRombel->id,
            'academic_year_id' => $this->oldAy->id,
            'is_active' => false,
            'join_date' => '2024-07-01',
            'leave_date' => '2025-06-30',
            'attendance_number' => 1,
        ]);
        // Penempatan TA aktif.
        StudentClassHistory::create([
            'student_id' => $this->activeStudent->id,
            'study_group_id' => $this->activeRombel->id,
            'academic_year_id' => $this->activeAy->id,
            'is_active' => true,
            'join_date' => '2026-07-01',
            'attendance_number' => 1,
        ]);

        // Buku nilai + nilai TA lama.
        $this->editor = $this->makeUser('Koor Kurikulum', 'koor.kurikulum@test.local', 'Koordinator Kurikulum');

        $this->oldBook = TeacherAdminBook::create([
            'teacher_id' => $this->editor->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->oldRombel->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        NilaiSumatif::create([
            'admin_book_id' => $this->oldBook->id,
            'student_id' => $this->arsipStudent->id,
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'sts' => 88,
        ]);

        // Struktur Peta Kurikulum per periode + baris lama (NULL) sebagai fallback.
        GradeLevelSubject::create([
            'academic_year_id' => $this->oldAy->id,
            'grade_level_id' => $this->gradeOld->id,
            'subject_id' => $this->subject->id,
            'allocation_hours' => 4,
            'track' => null,
            'is_active' => true,
        ]);
        GradeLevelSubject::create([
            'academic_year_id' => $this->activeAy->id,
            'grade_level_id' => $this->gradeActive->id,
            'subject_id' => $this->subject->id,
            'allocation_hours' => 2,
            'track' => null,
            'is_active' => true,
        ]);
        GradeLevelSubject::create([
            'academic_year_id' => null,
            'grade_level_id' => $this->gradeOld->id,
            'subject_id' => $this->subject->id,
            'allocation_hours' => 5,
            'track' => null,
            'is_active' => true,
        ]);

        SubjectKktp::create([
            'subject_id' => $this->subject->id,
            'school_id' => $this->school->id,
            'grade_level_id' => $this->gradeOld->id,
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'kktp_score' => 70,
            'kkm_score' => 70,
            'created_by' => $this->editor->id,
        ]);

        $this->tu = $this->makeUser('TU Arsip TA', 'tu.arsip.ta@test.local', 'Staf Tata Usaha');
    }

    private function makeUser(string $name, string $email, string $jabatan): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'Satuan Pendidikan', 'guard_name' => 'web'],
            ['level' => 8]
        );

        foreach (['impersonate_role', 'school-update', 'student-all-access', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => $jabatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sidebar memakai gtk_work_unit → work unit → sekolah.
        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    // ─────────────────────────────────────────────────────────────
    // DATA NILAI — PERIODE & ARSIP
    // ─────────────────────────────────────────────────────────────

    public function test_data_nilai_dapat_memilih_tahun_ajaran_arsip(): void
    {
        $this->actingAs($this->tu);

        $view = app(NilaiController::class)->index(
            $this->makeRequest(['academic_year_id' => $this->oldAy->id]),
            $this->tu->id,
        );
        $data = $view->getData();

        $this->assertSame($this->oldAy->id, $data['selectedAcademicYearId']);
        $this->assertTrue($data['isArchiveAy'], 'TA lama harus ditandai sebagai arsip.');
        $this->assertTrue($data['academicYears']->contains('id', $this->activeAy->id));
        $this->assertTrue($data['academicYears']->contains('id', $this->oldAy->id));

        $kelasList = $data['kelasList'];
        $this->assertTrue(
            $kelasList->contains(fn ($k) => $k['study_group']->id === $this->oldRombel->id),
            'Buku/kelas TA lama harus tampil pada periode arsip.',
        );
        $this->assertFalse(
            $kelasList->contains(fn ($k) => $k['study_group']->id === $this->activeRombel->id),
            'Kelas TA aktif tidak boleh tercampur ke tampilan TA lama.',
        );
    }

    public function test_input_nilai_tahun_ajaran_arsip_ditolak(): void
    {
        $this->actingAs($this->tu);

        $request = $this->makeRequest([
            'tab' => 'leger',
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'leger_sts' => [
                $this->arsipStudent->id => [
                    (string) $this->oldBook->id => ['sts' => 95],
                ],
            ],
        ]);

        try {
            app(NilaiKelasController::class)->stsStore($request, $this->tu->id, $this->oldRombel->id);
            $this->fail('Menyimpan nilai TA arsip seharusnya ditolak (403).');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertDatabaseHas('admin_nilai_sumatif', [
            'admin_book_id' => $this->oldBook->id,
            'student_id' => $this->arsipStudent->id,
            'sts' => 88,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // PETA KURIKULUM — ARSIP PERIODE & AKTIVASI
    // ─────────────────────────────────────────────────────────────

    public function test_peta_kurikulum_mengikuti_tahun_ajaran_terpilih(): void
    {
        $this->actingAs($this->editor);

        $old = app(KurikulumController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->oldAy->id]), $this->editor->id)
            ->getData();
        $this->assertTrue($old['isArchive']);

        $oldRow = collect($old['rows'])->first(
            fn ($r) => $r['grade_level_id'] === $this->gradeOld->id && $r['subject_id'] === $this->subject->id
        );
        $this->assertNotNull($oldRow, 'Baris struktur TA lama harus tampil.');
        $this->assertSame(4, $oldRow['weekly_hours'], 'Versi periode TA lama harus diprioritaskan atas baris lama (NULL).');

        $active = app(KurikulumController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->activeAy->id]), $this->editor->id)
            ->getData();
        $this->assertFalse($active['isArchive']);

        $activeRow = collect($active['rows'])->first(
            fn ($r) => $r['grade_level_id'] === $this->gradeActive->id && $r['subject_id'] === $this->subject->id
        );
        $this->assertNotNull($activeRow);
        $this->assertSame(2, $activeRow['weekly_hours']);
    }

    public function test_siapkan_peta_kurikulum_menyalin_periode_tanpa_menimpa_arsip(): void
    {
        $this->actingAs($this->editor);

        $request = $this->makeRequest([
            'academic_year_id' => $this->targetAy->id,
            'source_academic_year_id' => $this->oldAy->id,
        ]);

        $response = app(KurikulumController::class)->siapkan($request, $this->editor->id);
        $this->assertTrue($response->isRedirect());

        // Struktur sumber (efektif) disalin ke TA tujuan.
        $this->assertDatabaseHas('grade_level_subjects', [
            'academic_year_id' => $this->targetAy->id,
            'grade_level_id' => $this->gradeOld->id,
            'subject_id' => $this->subject->id,
            'allocation_hours' => 4,
        ]);
        $this->assertDatabaseHas('subject_kktp', [
            'academic_year_id' => $this->targetAy->id,
            'grade_level_id' => $this->gradeOld->id,
            'subject_id' => $this->subject->id,
            'kktp_score' => 70,
        ]);

        // Arsip TA sumber & baris lama tidak berubah.
        $this->assertDatabaseHas('grade_level_subjects', [
            'academic_year_id' => $this->oldAy->id,
            'grade_level_id' => $this->gradeOld->id,
            'allocation_hours' => 4,
        ]);
        $this->assertDatabaseHas('grade_level_subjects', [
            'academic_year_id' => null,
            'grade_level_id' => $this->gradeOld->id,
            'allocation_hours' => 5,
        ]);

        // Idempoten: menjalankan ulang tidak menggandakan baris.
        app(KurikulumController::class)->siapkan($request, $this->editor->id);
        $this->assertSame(1, GradeLevelSubject::where('academic_year_id', $this->targetAy->id)
            ->where('grade_level_id', $this->gradeOld->id)
            ->where('subject_id', $this->subject->id)
            ->count());
    }

    public function test_siapkan_peta_kurikulum_hanya_untuk_pengguna_berwenang(): void
    {
        $this->actingAs($this->tu);

        $request = $this->makeRequest([
            'academic_year_id' => $this->targetAy->id,
            'source_academic_year_id' => $this->oldAy->id,
        ]);

        try {
            app(KurikulumController::class)->siapkan($request, $this->tu->id);
            $this->fail('TU seharusnya tidak boleh menyiapkan Peta Kurikulum.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_hapus_mapel_pada_ta_aktif_tidak_menghapus_arsip_periode_lain(): void
    {
        // Struktur lama (lintas periode) untuk mapel kedua — satu-satunya sumber
        // yang dipakai TA lama agar tampil.
        $legacySubject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'BIG', 'name' => 'Bahasa Inggris',
            'credit_hours' => 3, 'is_active' => true,
        ]);
        $legacyRow = GradeLevelSubject::create([
            'academic_year_id' => null,
            'grade_level_id' => $this->gradeOld->id,
            'subject_id' => $legacySubject->id,
            'allocation_hours' => 3,
            'track' => null,
            'is_active' => true,
        ]);

        $this->actingAs($this->tu)
            ->delete("/{$this->tu->id}/grade-levels/{$this->gradeOld->id}/subjects/remove", [
                'subject_id' => $legacyRow->id,
            ])
            ->assertRedirect();

        // Baris lama tidak dihapus — arsip periode lain tetap utuh.
        $this->assertDatabaseHas('grade_level_subjects', [
            'id' => $legacyRow->id,
            'academic_year_id' => null,
        ]);

        // TA aktif memiliki shadow nonaktif (mapel hilang hanya untuk TA aktif).
        $this->assertDatabaseHas('grade_level_subjects', [
            'academic_year_id' => $this->activeAy->id,
            'grade_level_id' => $this->gradeOld->id,
            'subject_id' => $legacySubject->id,
            'is_active' => 0,
        ]);

        // Peta Kurikulum TA lama tetap menampilkan mapel tersebut dari struktur lama.
        $old = app(KurikulumController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->oldAy->id]), $this->editor->id)
            ->getData();
        $this->assertTrue(collect($old['rows'])->contains(
            fn ($r) => $r['grade_level_id'] === $this->gradeOld->id && $r['subject_id'] === $legacySubject->id
        ), 'Arsip TA lama harus tetap menampilkan mapel dari struktur lama.');

        // Peta Kurikulum TA aktif tidak lagi menampilkan mapel yang dihapus.
        $active = app(KurikulumController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->activeAy->id]), $this->editor->id)
            ->getData();
        $this->assertFalse(collect($active['rows'])->contains(
            fn ($r) => $r['grade_level_id'] === $this->gradeOld->id && $r['subject_id'] === $legacySubject->id
        ), 'Mapel yang dihapus tidak boleh tampil pada TA aktif.');
    }

    // ─────────────────────────────────────────────────────────────
    // DATA SANTRI — FILTER TA & KOLOM ROMBEL
    // ─────────────────────────────────────────────────────────────

    public function test_data_santri_filter_tahun_ajaran_dan_kolom_rombel_periode(): void
    {
        $this->actingAs($this->tu);

        $old = app(StudentController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->oldAy->id]))
            ->getData();

        $oldNames = collect($old['students']->items())->pluck('name');
        $this->assertTrue($oldNames->contains('Santri Arsip Lama'), 'Santri TA lama harus tampil saat filter TA lama.');
        $this->assertFalse($oldNames->contains('Santri Aktif Baru'), 'Santri tanpa penempatan TA lama tidak boleh tampil.');
        $this->assertSame($this->oldAy->id, $old['rombelDisplayAyId']);
        $this->assertTrue($old['isArchiveAy']);

        $arsip = collect($old['students']->items())->firstWhere('name', 'Santri Arsip Lama');
        $placement = $arsip->classHistories->firstWhere('academic_year_id', $this->oldAy->id);
        $this->assertNotNull($placement, 'Data penempatan periode harus tersedia untuk kolom Rombel.');
        $this->assertSame($this->oldRombel->id, $placement->study_group_id);

        $active = app(StudentController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->activeAy->id]))
            ->getData();
        $activeNames = collect($active['students']->items())->pluck('name');
        $this->assertTrue($activeNames->contains('Santri Aktif Baru'));
        $this->assertFalse($activeNames->contains('Santri Arsip Lama'));
        $this->assertFalse($active['isArchiveAy']);
    }

    public function test_data_santri_per_rombel_arsip_mengikuti_periode_rombel(): void
    {
        $this->actingAs($this->tu);

        $view = app(StudentController::class)->index($this->makeRequest([
            'study_group_id' => $this->oldRombel->id,
        ]))->getData();

        $names = collect($view['students']->items())->pluck('name');
        $this->assertTrue($names->contains('Santri Arsip Lama'), 'Anggota rombel arsip tetap tampil.');
        $this->assertFalse($names->contains('Santri Aktif Baru'));
        $this->assertSame($this->oldAy->id, $view['rombelDisplayAyId']);
        $this->assertTrue($view['isArchiveAy']);
    }

    // ─────────────────────────────────────────────────────────────
    // DATA ROMBEL — AKSI CEPAT KE SANTRI PERIODE ROMBEL
    // ─────────────────────────────────────────────────────────────

    public function test_data_rombel_statistik_dan_aksi_cepat_mengikuti_periode(): void
    {
        $this->actingAs($this->tu);

        // Controller: statistik & hitungan santri mengikuti TA rombel (arsip).
        $view = app(StudyGroupController::class)->index(
            $this->makeRequest(['academic_year_id' => $this->oldAy->id]),
            $this->tu->id,
        );
        $data = $view->getData();

        $oldGroup = $data['studyGroups']->getCollection()->firstWhere('id', $this->oldRombel->id);
        $this->assertNotNull($oldGroup, 'Rombel TA lama harus tampil pada Data Rombel.');
        $this->assertSame(1, (int) $oldGroup->studentCount, 'Anggota arsip (is_active=false) tetap dihitung pada periode lama.');
        $this->assertSame(1, $data['stats']['santri']);

        // HTTP: tautan aksi cepat membawa konteks rombel + TA arsip.
        $this->get("/{$this->tu->id}/study-groups?academic_year_id={$this->oldAy->id}")
            ->assertOk()
            ->assertSee('study_group_id='.$this->oldRombel->id, false)
            ->assertSee('academic_year_id='.$this->oldAy->id, false);
    }

    // ─────────────────────────────────────────────────────────────
    // SIDEBAR — HANYA ROMBEL TA AKTIF
    // ─────────────────────────────────────────────────────────────

    public function test_sidebar_hanya_menampilkan_rombel_tahun_ajaran_aktif(): void
    {
        $response = $this->actingAs($this->tu)->get("/{$this->tu->id}/students");

        $response->assertOk()
            ->assertSee('7A-AKTIF')
            ->assertDontSee('6B-ARSIP');

        // Data rombel lama tetap tersimpan & dapat diakses via Data Rombel.
        $this->assertDatabaseHas('study_groups', [
            'id' => $this->oldRombel->id,
            'academic_year_id' => $this->oldAy->id,
        ]);
    }

    private function makeRequest(array $data = []): Request
    {
        $request = Request::create('/arsip-tahun-ajaran', 'GET', $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => auth()->user());

        return $request;
    }
}
