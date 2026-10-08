<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\SumatifHarianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SumatifHarianDinamisTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $studyGroup;

    private Subject $subject;

    private User $teacher;

    private TeacherAdminBook $book;

    private string $gradeLevelId;

    /** @var array<int, Student> */
    private array $students = [];

    private int $baseObLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseObLevel = ob_get_level();

        // Permission yang dicek langsung oleh layout sidebar.
        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Nilai', 'code' => 'UUN',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88888888',
            'name' => 'Sekolah Uji Nilai',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $this->gradeLevelId, 'school_id' => $this->school->id, 'level' => 8, 'name' => 'Kelas 8',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->gradeLevelId,
            'name' => '8A', 'code' => '8A', 'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Satuan Pendidikan', 'guard_name' => 'web', 'level' => 3]);

        $this->teacher = User::create([
            'name' => 'Guru Nilai',
            'email' => 'guru.nilai@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->teacher->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->teacher->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 2; $i++) {
            $student = Student::create([
                'school_id' => $this->school->id,
                'nisn' => '99900000'.$i,
                'name' => 'Santri '.$i,
                'gender' => 'L',
            ]);
            $this->students[] = $student;

            DB::table('student_class_histories')->insert([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'study_group_id' => $this->studyGroup->id,
                'academic_year_id' => $this->ay->id,
                'is_active' => 1,
                'attendance_number' => $i,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->book = TeacherAdminBook::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // Bersihkan buffer yang mungkin tertinggal dari render halaman legacy/grid.
        while (ob_get_level() > $this->baseObLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // SERVICE: kolom dinamis + aturan RS yang sama
    // ─────────────────────────────────────────────────────────────

    public function test_dynamic_columns_added_and_rs_uses_all_filled_values(): void
    {
        $service = app(SumatifHarianService::class);

        // Default = S1–S6 legacy
        $columns = $service->columnsFor($this->book);
        $this->assertSame(SumatifHarianService::LEGACY_IDS, array_column($columns, 'id'));

        // Tambah 2 kolom baru + rename label S1
        $newId1 = (string) Str::uuid();
        $newId2 = (string) Str::uuid();
        $saved = $service->saveColumns($this->book, [
            ['id' => 's1', 'label' => 'Sumatif Harian 1'],
            ...array_map(fn ($id, $label) => ['id' => $id, 'label' => $label], array_slice(SumatifHarianService::LEGACY_IDS, 1), ['Sumatif 2', 'Sumatif 3', 'Sumatif 4', 'Sumatif 5', 'Sumatif 6']),
            ['id' => $newId1, 'label' => 'Praktik'],
            ['id' => $newId2, 'label' => 'Proyek'],
        ]);

        $this->assertCount(8, $saved);
        $this->assertSame('Praktik', $saved[6]['label']);
        $this->assertFalse($saved[6]['legacy']);

        // Isi nilai: s1=80, s2=90, kolom baru=100 & 70 → RS = 85
        $row = $service->upsertSumatif($this->book, $this->students[0]->id, [
            'sh' => ['s1' => 80, 's2' => 90, $newId1 => 100, $newId2 => 70],
            'sts' => 88,
            'sas' => 92,
        ]);

        $this->assertSame('80.00', $row->s1);
        $this->assertSame('90.00', $row->s2);
        $this->assertSame(85.0, (float) $row->rs);
        $this->assertSame(100.0, (float) $row->sumatif_harian[$newId1]);
        $this->assertSame(70.0, (float) $row->sumatif_harian[$newId2]);

        // Nilai turunan terisi seragam
        $this->assertNotNull($row->rsa);
        $this->assertNotNull($row->nr_murni);
        $this->assertNotNull($row->nr_final);
    }

    public function test_delete_dynamic_column_prunes_values_and_recalcs(): void
    {
        $service = app(SumatifHarianService::class);
        $newId = (string) Str::uuid();

        $service->saveColumns($this->book, [
            ['id' => 's1', 'label' => 'S1'],
            ['id' => $newId, 'label' => 'Praktik'],
        ]);

        $row = $service->upsertSumatif($this->book, $this->students[0]->id, [
            'sh' => ['s1' => 80, $newId => 100],
        ]);
        $this->assertSame(90.0, (float) $row->rs);

        // Hapus kolom dinamis → nilai ikut terhapus & RS dihitung ulang
        $service->saveColumns($this->book, [
            ['id' => 's1', 'label' => 'S1'],
        ]);

        $row->refresh();
        $this->assertNull($row->sumatif_harian);
        $this->assertSame(80.0, (float) $row->rs);
    }

    public function test_legacy_values_readable_and_partial_update_does_not_wipe_sh(): void
    {
        $service = app(SumatifHarianService::class);

        // Data legacy: hanya kolom fisik s1..s6 yang terisi (JSON null)
        $legacy = NilaiSumatif::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $this->students[0]->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            's1' => 70, 's2' => 90,
        ]);

        $columns = $service->columnsFor($this->book);
        $values = $service->valuesFor($legacy, $columns);
        $this->assertSame(70.0, $values['s1']);
        $this->assertSame(90.0, $values['s2']);

        // Partial update (hanya SAS) tidak boleh menghapus SH
        $updated = $service->upsertSumatif($this->book, $this->students[0]->id, ['sas' => 95]);
        $this->assertSame('70.00', $updated->s1);
        $this->assertSame('90.00', $updated->s2);
        $this->assertSame(80.0, (float) $updated->rs);
        $this->assertSame('95.00', $updated->sas);
    }

    // ─────────────────────────────────────────────────────────────
    // HTTP: wizard, autosave, grid wali kelas, legacy → RS sama
    // ─────────────────────────────────────────────────────────────

    public function test_all_input_paths_use_same_rs_rule(): void
    {
        $this->actingAs($this->teacher);
        $studentId = $this->students[0]->id;

        // 1) Wizard3 (Buku Administrasi) — sh map
        $this->post("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w3", [
            'sumatif' => [
                $studentId => [
                    'sh' => ['s1' => 80, 's2' => 100],
                    'sts' => 90,
                    'sas' => 85,
                ],
            ],
        ])->assertRedirect();

        $row = NilaiSumatif::where('admin_book_id', $this->book->id)->where('student_id', $studentId)->first();
        $this->assertSame(90.0, (float) $row->rs);

        // 2) Autosave — payload sama
        $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/autosave", [
            'type' => 'sumatif',
            'sumatif' => [
                $studentId => ['sh' => ['s1' => 80, 's2' => 100], 'sts' => 90, 'sas' => 85],
            ],
        ])->assertOk()->assertJson(['saved' => true]);

        $row->refresh();
        $this->assertSame(90.0, (float) $row->rs);

        // 3) Grid wali kelas (nilai-kelas sts store, tab mapel)
        $this->post("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts", [
            'tab' => 'mapel',
            'admin_book_id' => $this->book->id,
            'nilai' => [
                $studentId => ['sh' => ['s1' => 80, 's2' => 100], 'sts' => 90],
            ],
        ])->assertRedirect();

        $row->refresh();
        $this->assertSame(90.0, (float) $row->rs);

        // 4) Halaman legacy (nilai sts store) — format lama s1/s2 tanpa sh[]
        $this->post("/{$this->teacher->id}/schools/nilai/{$this->book->id}/sts", [
            'nilai' => [
                $studentId => ['s1' => 80, 's2' => 100, 'sts' => 90],
            ],
        ])->assertRedirect();

        $row->refresh();
        $this->assertSame(90.0, (float) $row->rs, 'RS harus sama di semua jalur input');
        $this->assertNotNull($row->nr_final, 'NR Final kini terisi di semua jalur (unified).');
    }

    // ─────────────────────────────────────────────────────────────
    // LEGER & RAPOR tetap membaca STS + aturan predikat sama
    // ─────────────────────────────────────────────────────────────

    public function test_columns_endpoint_saves_definitions(): void
    {
        $this->actingAs($this->teacher);

        $newId = (string) Str::uuid();

        $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w3/kolom", [
            'columns' => [
                ['id' => 's1', 'label' => 'Sumatif Harian 1'],
                ['id' => 's2', 'label' => 'Sumatif 2'],
                ['id' => 's3', 'label' => 'Sumatif 3'],
                ['id' => 's4', 'label' => 'Sumatif 4'],
                ['id' => 's5', 'label' => 'Sumatif 5'],
                ['id' => 's6', 'label' => 'Sumatif 6'],
                ['id' => $newId, 'label' => 'Praktik'],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $columns = app(SumatifHarianService::class)->columnsFor($this->book->fresh());
        $this->assertCount(7, $columns);
        $this->assertSame('Praktik', $columns[6]['label']);
        $this->assertFalse($columns[6]['legacy']);
    }

    public function test_wizard3_and_grid_pages_render_with_dynamic_columns(): void
    {
        $service = app(SumatifHarianService::class);
        $newId = (string) Str::uuid();

        $service->saveColumns($this->book, [
            ['id' => 's1', 'label' => 'Sumatif Harian 1'],
            ['id' => $newId, 'label' => 'Praktik'],
        ]);

        $service->upsertSumatif($this->book, $this->students[0]->id, [
            'sh' => ['s1' => 80, $newId => 95],
            'sts' => 90,
        ]);

        $this->actingAs($this->teacher);

        // Wizard3 (Buku Administrasi) — kolom dinamis + tombol kelola kolom
        $this->get("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w3")
            ->assertOk()
            ->assertSee('Kelola Kolom')
            ->assertSee('Praktik');

        // Grid wali kelas — kolom dinamis ikut tampil (tab mapel)
        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts?tab=mapel&admin_book_id={$this->book->id}")
            ->assertOk()
            ->assertSee('Praktik');

        // Halaman legacy — kolom dinamis ikut tampil
        $this->get("/{$this->teacher->id}/schools/nilai/{$this->book->id}/sts")
            ->assertOk()
            ->assertSee('Praktik');
    }

    public function test_rapor_note_saved_and_leger_uses_kktp_term(): void
    {
        $service = app(SumatifHarianService::class);
        $service->upsertSumatif($this->book, $this->students[0]->id, ['sh' => ['s1' => 80], 'sts' => 90, 'sas' => 90]);

        $this->actingAs($this->teacher);

        // Halaman rapor index + tombol catatan wali kelas
        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor?academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('Catatan');

        // Simpan catatan wali kelas (kolom existing homeroom_note)
        $this->post("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/{$this->students[0]->id}/catatan", [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'homeroom_note' => 'Pertahankan semangat belajar.',
        ])->assertRedirect();

        $this->assertDatabaseHas('raport_registrations', [
            'student_id' => $this->students[0]->id,
            'homeroom_note' => 'Pertahankan semangat belajar.',
        ]);

        // Rapor SAS render PDF dan menyimpan final_score/predicate (nilai akhir).
        // nr_final = (80×50 + 90×25 + 90×25)/100 = 85
        $rapor = $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/{$this->students[0]->id}/cetak?academic_year_id={$this->ay->id}&semester=ganjil&jenis=sas");
        $rapor->assertOk();

        $this->assertDatabaseHas('raport_registrations', [
            'student_id' => $this->students[0]->id,
            'predicate' => \App\Support\AcademicNilai::predikat(85.0),
        ]);

        // Istilah seragam KKTP di Leger (tidak ada lagi "KKM")
        $leger = $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/leger/cetak?academic_year_id={$this->ay->id}&semester=ganjil");
        $leger->assertOk()->assertSee('KKTP')->assertDontSee('KKM');
    }

    public function test_leger_sas_and_sts_show_different_scores(): void
    {
        $service = app(SumatifHarianService::class);
        // nr_final = (80×50 + 90×25 + 80×25)/100 = 82.5
        $service->upsertSumatif($this->book, $this->students[0]->id, ['sh' => ['s1' => 80], 'sts' => 90, 'sas' => 80]);

        $this->actingAs($this->teacher);
        $base = "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/leger/cetak?academic_year_id={$this->ay->id}&semester=ganjil";

        // Leger STS → nilai STS (90), judul Tengah Semester
        $sts = $this->get($base.'&jenis=sts');
        $sts->assertOk()->assertSee('SUMATIF TENGAH SEMESTER (STS)')->assertSee('90');

        // Leger SAS → Nilai Akhir / NR Final (82.5 → dibulatkan 83), judul Akhir Semester
        $sas = $this->get($base.'&jenis=sas');
        $sas->assertOk()->assertSee('AKHIR SEMESTER (SAS)')->assertSee('83');

        // Rapor SAS pakai nilai akhir; Rapor STS tetap jalan
        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/{$this->students[0]->id}/cetak?academic_year_id={$this->ay->id}&semester=ganjil&jenis=sas")
            ->assertOk();

        $this->assertDatabaseHas('raport_registrations', [
            'student_id' => $this->students[0]->id,
            'final_score' => 82.5,
        ]);
    }

    public function test_kktp_single_value_writes_both_columns(): void
    {
        $this->actingAs($this->teacher);

        // Simpan satu nilai KKTP dari halaman KKTP admin
        $this->post("/{$this->teacher->id}/schools/kktp", [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'grade_level_id' => $this->gradeLevelId,
            'kktp' => [
                $this->subject->id => ['kktp_score' => 78, 'notes' => 'uji kktp'],
            ],
        ])->assertRedirect();

        $row = DB::table('subject_kktp')
            ->where('subject_id', $this->subject->id)
            ->where('grade_level_id', $this->gradeLevelId)
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(78.0, (float) $row->kktp_score);
        $this->assertEquals(78.0, (float) $row->kkm_score, 'kkm_score harus mirror nilai KKTP');

        // Simpan dari halaman grade-levels → kedua kolom tetap sama
        $this->post("/{$this->teacher->id}/grade-levels/{$this->gradeLevelId}/kktp", [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'kktp' => [$this->subject->id => 82],
        ])->assertRedirect();

        $row = DB::table('subject_kktp')
            ->where('subject_id', $this->subject->id)
            ->where('grade_level_id', $this->gradeLevelId)
            ->first();

        $this->assertEquals(82.0, (float) $row->kktp_score);
        $this->assertEquals(82.0, (float) $row->kkm_score);
    }

    public function test_leger_and_rapor_render_with_shared_rules(): void
    {
        $service = app(SumatifHarianService::class);

        foreach ($this->students as $i => $student) {
            $service->upsertSumatif($this->book, $student->id, [
                'sh' => ['s1' => 80],
                'sts' => $i === 0 ? 95 : 70,
            ]);
        }

        $this->actingAs($this->teacher);

        // Leger cetak
        $leger = $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/leger/cetak");
        $leger->assertOk();
        $leger->assertSee('LEGER');

        // Rapor cetak (PDF)
        $rapor = $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/{$this->students[0]->id}/cetak");
        $rapor->assertOk();
        $this->assertStringContainsString('application/pdf', $rapor->headers->get('Content-Type'));

        // Aturan predikat sama di dua tempat
        $this->assertSame("Mumtaz Murtafi'", \App\Support\AcademicNilai::predikat(96.0));
        $this->assertSame('Roosib', \App\Support\AcademicNilai::predikat(70.0));
        $this->assertSame('—', \App\Support\AcademicNilai::predikat(null));
    }
}
