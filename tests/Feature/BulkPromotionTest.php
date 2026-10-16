<?php

namespace Tests\Feature;

use App\Http\Controllers\BulkPromotionController;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi kenaikan kelas (bulk promotion):
 * - TA asal diambil dari rombel sumber (bukan TA aktif global),
 * - rombel tujuan otomatis memilih KELAS PARALEL (1A → 2A, bukan 2B),
 * - enroll idempoten (re-run tidak menggandakan & tidak menyisakan kelas kosong),
 * - halaman kelas tujuan menampilkan santri hasil kenaikan.
 */
class BulkPromotionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay1;

    private AcademicYear $ay2;

    private StudyGroup $class1A;

    private StudyGroup $class2A;

    private StudyGroup $class2B;

    private Student $student;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Promosi', 'code' => 'UUP',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '33333333',
            'name' => 'Sekolah Uji Promosi',
        ]);

        $this->ay1 = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $this->ay2 = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade1 = GradeLevel::create(['school_id' => $this->school->id, 'level' => 1, 'name' => 'Kelas 1', 'is_active' => true]);
        $grade2 = GradeLevel::create(['school_id' => $this->school->id, 'level' => 2, 'name' => 'Kelas 2', 'is_active' => true]);

        $this->class1A = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay1->id,
            'grade_level_id' => $grade1->id, 'name' => '1A', 'code' => '1A', 'capacity' => 30, 'is_active' => true,
        ]);

        // 2B dibuat lebih dulu → membuktikan pemilihan target TIDAK asal rombel pertama.
        $this->class2B = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay2->id,
            'grade_level_id' => $grade2->id, 'name' => '2B', 'code' => '2B', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->class2A = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay2->id,
            'grade_level_id' => $grade2->id, 'name' => '2A', 'code' => '2A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Naik Kelas',
            'nisn' => '1112223334', 'gender' => 'L', 'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->class1A->id,
            'academic_year_id' => $this->ay1->id,
            'is_active' => true,
            'join_date' => '2025-07-01',
            'attendance_number' => 1,
        ]);

        $this->operator = User::create([
            'name' => 'Operator Promosi', 'email' => 'op.promosi@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
    }

    public function test_kenaikan_kelas_paralel_1a_ke_2a_dan_kelas_tujuan_berisi_data(): void
    {
        $controller = app(BulkPromotionController::class);

        // Jalankan kenaikan kelas tanpa memilih rombel tujuan → harus memilih 2A (paralel).
        $controller->promote($this->makeRequest([
            'from_study_group_id' => $this->class1A->id,
            'to_academic_year_id' => $this->ay2->id,
            'promotion_date' => '2026-07-01',
            'student_ids' => [$this->student->id],
        ]), $this->operator->id);

        // Histori lama ditutup pada TA ASAL (1A/2025-2026).
        $old = StudentClassHistory::where('student_id', $this->student->id)
            ->where('study_group_id', $this->class1A->id)
            ->first();
        $this->assertNotNull($old);
        $this->assertFalse((bool) $old->is_active);

        // Histori baru ada di 2A (bukan 2B).
        $new = StudentClassHistory::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->ay2->id)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($new, 'Santri harus terdaftar pada tahun ajaran tujuan.');
        $this->assertSame($this->class2A->id, $new->study_group_id, 'Rombel tujuan harus kelas paralel 2A, bukan 2B.');

        // Halaman kelas tujuan (2A) menampilkan santri hasil kenaikan.
        $view = $controller->index($this->makeRequest([], 'GET'), $this->operator->id, $this->class2A->id);
        $students = $view->getData()['students'];
        $this->assertTrue(
            $students->contains(fn ($s) => $s->id === $this->student->id),
            'Kelas 2A harus menampilkan santri hasil kenaikan kelas.',
        );
    }

    public function test_kenaikan_kelas_idempoten_tidak_menggandakan_histori(): void
    {
        $controller = app(BulkPromotionController::class);

        $payload = [
            'from_study_group_id' => $this->class1A->id,
            'to_academic_year_id' => $this->ay2->id,
            'promotion_date' => '2026-07-01',
            'student_ids' => [$this->student->id],
        ];

        $controller->promote($this->makeRequest($payload), $this->operator->id);

        // Jalankan ulang (mis. operator menekan tombol dua kali) → tidak boleh menggandakan.
        $controller->promote($this->makeRequest($payload), $this->operator->id);

        $this->assertSame(
            1,
            StudentClassHistory::where('student_id', $this->student->id)
                ->where('academic_year_id', $this->ay2->id)
                ->count(),
            'Re-run kenaikan kelas tidak boleh menggandakan histori pada TA tujuan.',
        );

        $new = StudentClassHistory::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->ay2->id)->first();
        $this->assertSame($this->class2A->id, $new->study_group_id);
        $this->assertTrue((bool) $new->is_active);
    }

    public function test_ta_sama_ditolak_gunakan_fitur_mutasi_kelas(): void
    {
        // Rombel lain pada TA yang SAMA — tidak boleh diproses di halaman kenaikan kelas.
        $class1B = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay1->id,
            'grade_level_id' => $this->class1A->grade_level_id, 'name' => '1B', 'code' => '1B',
            'capacity' => 30, 'is_active' => true,
        ]);

        $controller = app(BulkPromotionController::class);
        $controller->promote($this->makeRequest([
            'from_study_group_id' => $this->class1A->id,
            'to_academic_year_id' => $this->ay1->id, // TA sama
            'to_study_group_id' => $class1B->id,
            'promotion_date' => '2026-01-05',
            'student_ids' => [$this->student->id],
        ]), $this->operator->id);

        $this->assertTrue(session()->has('errors'), 'TA sama harus ditolak (gunakan Mutasi Kelas).');
        $this->assertNotEmpty(session('errors')->get('to_academic_year_id'));

        // Data tidak berubah: histori tetap aktif di rombel asal.
        $row = StudentClassHistory::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->ay1->id)->first();
        $this->assertTrue((bool) $row->is_active);
        $this->assertSame($this->class1A->id, $row->study_group_id);
    }

    public function test_ta_baru_tanpa_rombel_tujuan_tidak_mengubah_data(): void
    {
        // TA baru (ay2) tanpa rombel → promosi harus GAGAL tanpa menutup histori lama
        // (regresi: sebelumnya histori ditutup tanpa baris baru → santri kehilangan kelas).
        $this->class2A->delete();
        $this->class2B->delete();

        $controller = app(BulkPromotionController::class);
        $controller->store($this->makeRequest([
            'to_academic_year_id' => $this->ay2->id,
            'promotion_date' => '2026-07-01',
            'student_ids' => [$this->student->id],
        ]), $this->operator->id, $this->class1A->id);

        $this->assertTrue(session()->has('errors'), 'Target kosong harus ditolak sebelum mengubah data.');
        $this->assertNotEmpty(session('errors')->get('to_study_group_id'));

        // Histori lama TETAP aktif; tidak ada baris pada TA baru.
        $old = StudentClassHistory::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->ay1->id)->first();
        $this->assertTrue((bool) $old->is_active, 'Histori lama tidak boleh ditutup bila target tidak ada.');
        $this->assertSame(0, StudentClassHistory::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->ay2->id)->count());
    }

    private function makeRequest(array $data, string $method = 'POST'): Request
    {
        $request = Request::create('/bulk-promotion/promote', $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->operator);

        return $request;
    }
}
