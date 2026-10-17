<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PembiasaanPagi;
use App\Models\PenghargaanAkademik;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzJuzProgress;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\SumatifHarianService;
use App\Support\AcademicNilai;
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
        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

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

        // Modul leger kini dibatasi: guru pada fixture ini adalah wali kelas rombel uji.
        $this->studyGroup->forceFill(['homeroom_teacher_id' => $this->teacher->id])->save();
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
                    'sts_murni' => 90,
                    'sts' => 90,
                    'sas_murni' => 85,
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
                $studentId => ['sh' => ['s1' => 80, 's2' => 100], 'sts_murni' => 90, 'sts' => 90, 'sas_murni' => 85, 'sas' => 85],
            ],
        ])->assertOk()->assertJson(['saved' => true]);

        $row->refresh();
        $this->assertSame(90.0, (float) $row->rs);

        // 3) Grid wali kelas (nilai-kelas sts store, tab mapel)
        $this->post("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts", [
            'tab' => 'mapel',
            'admin_book_id' => $this->book->id,
            'nilai' => [
                $studentId => ['sh' => ['s1' => 80, 's2' => 100], 'sts_murni' => 90, 'sts' => 90],
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

        // Wizard3 (Buku Administrasi) — kolom dinamis + tombol kelola kolom + modal murni
        $this->get("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w3")
            ->assertOk()
            ->assertSee('Kelola Kolom')
            ->assertSee('murniModalW3')
            ->assertSee('Praktik')
            // Regresi: CSS kolom beku tidak boleh terjebak di dalam @if(session('success'))
            ->assertSee('.nilai-scroll { max-height: 72vh', false)
            ->assertSee('#sumatif-table thead tr:first-child th:nth-child(-n+3)', false)
            ->assertSee('#sumatif-table tbody td:nth-child(-n+3)', false);

        // Grid wali kelas — kolom dinamis ikut tampil (tab mapel)
        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts?tab=mapel&admin_book_id={$this->book->id}")
            ->assertOk()
            ->assertSee('Praktik');

        // Halaman legacy kini dialihkan ke leger baru (sheet per-mapel buku tsb)
        $legacy = $this->get("/{$this->teacher->id}/schools/nilai/{$this->book->id}/sts");
        $legacy->assertRedirect();
        $this->get($legacy->headers->get('Location'))
            ->assertOk()
            ->assertSee('Praktik');
    }

    public function test_rapor_note_saved_and_leger_uses_kktp_term(): void
    {
        $service = app(SumatifHarianService::class);
        $service->upsertSumatif($this->book, $this->students[0]->id, ['sh' => ['s1' => 80], 'sts' => 90, 'sas' => 90]);

        $this->actingAs($this->teacher);

        // Kebijakan Tahap 3: catatan rapor = wali kelas. Tetapkan guru sebagai wali rombel.
        $this->studyGroup->forceFill(['homeroom_teacher_id' => $this->teacher->id])->save();

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
            'predicate' => AcademicNilai::predikat(85.0),
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

        // Halaman KKTP admin lama sudah dihapus — KKTP diatur di Peta Kurikulum (kolom N-KKTP).
        // Jalur berikut menguji halaman grade-levels: satu nilai KKTP → kedua kolom mirror.
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
        $this->assertSame("Mumtaz Murtafi'", AcademicNilai::predikat(96.0));
        $this->assertSame('Roosib', AcademicNilai::predikat(70.0));
        $this->assertSame('—', AcademicNilai::predikat(null));
    }

    public function test_sheet_leger_sas_dan_kolom_lengkap_mapel_render(): void
    {
        $service = app(SumatifHarianService::class);
        $service->upsertSumatif($this->book, $this->students[0]->id, [
            'sh' => ['s1' => 80, 's2' => 90],
            'sts' => 88,
            'sas' => 92,
        ]);

        NilaiFormatif::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $this->students[0]->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'skor_lkpd' => 3.5,
            'skor_diskusi' => 4,
            'skor_kuis' => 3,
            'skor_antarteman' => 3.5,
        ]);

        PenghargaanAkademik::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $this->students[0]->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'jujur' => 4, 'disiplin' => 4, 'peduli' => 4, 'adab' => 4, 'kehadiran' => 4, 'keaktifan' => 4,
        ]);

        $this->actingAs($this->teacher);
        $base = "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts";

        // Sheet Leger SAS — judul dinamis + tabel read-only (tanpa input leger_sts)
        $this->get($base."?tab=leger&leger=sas&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('Leger Nilai SAS')
            ->assertDontSee('leger_sts[', false);

        // Sheet Leger STS kini baca-saja juga (input nilai di tab Mapel)
        $this->get($base."?tab=leger&leger=sts&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('Leger Nilai STS')
            ->assertSee('baca-saja')
            ->assertDontSee('leger_sts[', false);

        // Halaman leger: JS (urut/cari/autosave) dirender oleh layout — dan TIDAK ganda
        $legerPage = $this->get($base."?tab=leger&leger=sts&academic_year_id={$this->ay->id}&semester=ganjil");
        $legerPage->assertOk()->assertSee('initSortableTable', false);
        $this->assertSame(1, substr_count($legerPage->getContent(), 'const kolomColumnsUrl'), 'Skrip kolom tidak boleh dirender ganda.');
        $this->assertLegerGridRapi($legerPage->getContent());

        // Sheet per-mapel: kolom SAS/RSA/NR Murni/Penghargaan/Formatif ikut tampil
        $this->get($base."?tab=mapel&admin_book_id={$this->book->id}&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('Penghargaan Akademik')
            ->assertSee('NR Murni')
            ->assertSee('NR Final')
            ->assertSee('RATA-')
            ->assertSee('Penilaian Asesmen Formatif');
    }

    public function test_data_nilai_kewenangan_waka_dan_alur_wali_guru(): void
    {
        // Wali kelas → langsung ke Leger kelasnya (bukan Data Nilai umum)
        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/schools/nilai")
            ->assertRedirect(route('user.schools.nilai-kelas.sts', [
                'userId' => $this->teacher->id,
                'studyGroupId' => $this->studyGroup->id,
            ]));

        // Waka (kewenangan jabatan) → melihat seluruh rombel satuan pendidikan
        $waka = $this->makeSatuanUser('Wakil Kepala Satuan Pendidikan', 'waka.nilai@test.local');
        $this->actingAs($waka)
            ->get("/{$waka->id}/schools/nilai")
            ->assertOk()
            ->assertSee('8A');

        // Guru mapel non-wali → diarahkan ke Buku Nilai Saya
        $guru = $this->makeSatuanUser('Guru Umum', 'guru.mapel.nilai@test.local');
        TeacherAdminBook::create([
            'teacher_id' => $guru->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $this->actingAs($guru)
            ->get("/{$guru->id}/schools/nilai")
            ->assertRedirect(route('user.schools.guru-mapel.index', ['userId' => $guru->id]));

        // Sidebar: menu Data Nilai hanya untuk KSP/Waka/Kurikulum/TU
        $this->actingAs($guru)
            ->get("/{$guru->id}/todos")
            ->assertOk()
            ->assertDontSee('Data Nilai');
        $this->actingAs($waka)
            ->get("/{$waka->id}/todos")
            ->assertOk()
            ->assertSee('Data Nilai');
    }

    private function makeSatuanUser(string $jabatan, string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 3]);
        $user = User::create([
            'name' => 'Uji '.$jabatan,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
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

        return $user;
    }

    /** Pastikan grid tabel leger konsisten: seluruh baris selebar sama (tidak ada kolom ekstra/kurang). */
    private function assertLegerGridRapi(string $html): void
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);
        $table = $xp->query('//table[@id="tab-leger"]')->item(0);
        $this->assertNotNull($table, 'Tabel leger tidak ditemukan.');

        $occupied = [];
        $rowIndex = 0;
        foreach ($xp->query('./thead/tr | ./tbody/tr', $table) as $tr) {
            $col = 0;
            foreach ($xp->query('./th | ./td', $tr) as $cell) {
                while (isset($occupied[$rowIndex][$col])) { $col++; }
                $cs = max(1, (int) $cell->getAttribute('colspan'));
                $rs = max(1, (int) $cell->getAttribute('rowspan'));
                for ($r = $rowIndex; $r < $rowIndex + $rs; $r++) {
                    for ($c = $col; $c < $col + $cs; $c++) { $occupied[$r][$c] = true; }
                }
                $col += $cs;
            }
            $rowIndex++;
        }

        $maxCols = 0;
        for ($r = 0; $r < $rowIndex; $r++) { $maxCols = max($maxCols, count($occupied[$r] ?? [])); }
        $this->assertGreaterThan(0, $maxCols, 'Grid leger kosong.');
        for ($r = 0; $r < $rowIndex; $r++) {
            $this->assertSame($maxCols, count($occupied[$r] ?? []),
                'Baris tabel leger ke-'.($r + 1).' tidak selebar baris lain (indikasi kolom ekstra/kurang).');
        }
    }

    public function test_leger_sas_menampilkan_grup_agama_umum_pembiasaan_tahfidz(): void
    {
        // Mapel Agama (Fiqih) agar grup AGAMA tampil berdampingan dengan UMUM
        $agamaSubject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'FQH',
            'name' => 'Fiqih',
            'credit_hours' => 2,
            'is_active' => true,
        ]);
        TeacherAdminBook::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $agamaSubject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        // Pembiasaan Pagi (per buku mapel — agregasi per santri di leger)
        PembiasaanPagi::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $this->students[0]->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'skor_doa' => 90,
            'skor_hiwar' => 85,
            'skor_conversation' => 88,
        ]);

        // Progres juz tertinggi
        TahfidzJuzProgress::create([
            'student_id' => $this->students[0]->id,
            'academic_year_id' => $this->ay->id,
            'juz_number' => 5,
            'status' => 'sedang',
            'percentage' => 60,
        ]);

        // Halaqoh aktif
        $workUnitId = DB::table('work_units')->value('id');
        $groupId = (string) Str::uuid();
        DB::table('tahfidz_groups')->insert([
            'id' => $groupId,
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'name' => 'Halaqah Umar',
            'gender' => 'putra',
            'teacher_id' => $this->teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tahfidz_group_members')->insert([
            'id' => (string) Str::uuid(),
            'tahfidz_group_id' => $groupId,
            'student_id' => $this->students[0]->id,
            'academic_year_id' => $this->ay->id,
            'join_date' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->teacher);
        $base = "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts";

        // Leger SAS: grup PEMBIASAAN PAGI + TAHFIDZ + AGAMA/UMUM + nilai
        $sasPage = $this->get($base."?tab=leger&leger=sas&academic_year_id={$this->ay->id}&semester=ganjil");
        $sasPage->assertOk()
            ->assertSee('PEMBIASAAN PAGI')
            ->assertSee('TAHFIDZ')
            ->assertSee('Hiwar')
            ->assertSee('Conversation')
            ->assertSee('Halaqoh')
            ->assertSee('AGAMA')
            ->assertSee('UMUM')
            ->assertSee('Halaqah Umar')
            ->assertSee('90.0')
            ->assertDontSee('leger_sts[', false);
        $this->assertLegerGridRapi($sasPage->getContent());

        // Leger STS: header grup AGAMA/UMUM tampil, tanpa kolom Pembiasaan/Tahfidz
        $this->get($base."?tab=leger&leger=sts&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('AGAMA')
            ->assertSee('UMUM')
            ->assertDontSee('PEMBIASAAN PAGI');
    }

    public function test_index_mengarah_ke_leger_rombel_untuk_wali_kelas(): void
    {
        $this->studyGroup->forceFill(['homeroom_teacher_id' => $this->teacher->id])->save();

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/schools/nilai-kelas")
            ->assertRedirect(route('user.schools.nilai-kelas.sts', [
                'userId' => $this->teacher->id,
                'studyGroupId' => $this->studyGroup->id,
            ]));
    }

    public function test_guru_non_wali_ditolak_akses_leger(): void
    {
        $guruLain = User::create([
            'name' => 'Guru Non Wali',
            'email' => 'guru.nonwali@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $guruLain->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $guruLain->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($guruLain)
            ->get("/{$guruLain->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts")
            ->assertStatus(403);

        $this->actingAs($guruLain)
            ->get("/{$guruLain->id}/schools/nilai-kelas/{$this->studyGroup->id}/leger/cetak")
            ->assertStatus(403);
    }

    public function test_mapel_tab_wajib_nilai_murni_sebelum_kolom_sts_sas(): void
    {
        $this->actingAs($this->teacher);
        $url = "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts";
        $sid = $this->students[0]->id;

        // STS tanpa Nilai Murni → ditolak
        $this->post($url, [
            'tab' => 'mapel',
            'admin_book_id' => $this->book->id,
            'nilai' => [$sid => ['sts' => 80]],
        ])->assertSessionHasErrors("nilai.{$sid}.sts");

        // SAS tanpa Nilai Murni SAS → ditolak
        $this->post($url, [
            'tab' => 'mapel',
            'admin_book_id' => $this->book->id,
            'nilai' => [$sid => ['sas' => 82]],
        ])->assertSessionHasErrors("nilai.{$sid}.sas");

        // Isi Nilai Murni lebih dahulu → tersimpan
        $this->post($url, [
            'tab' => 'mapel',
            'admin_book_id' => $this->book->id,
            'nilai' => [$sid => ['sts_murni' => 85, 'sts' => 85, 'sas_murni' => 88, 'sas' => 88]],
        ])->assertSessionHasNoErrors();

        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(85.0, (float) $row->sts_murni);
        $this->assertSame(88.0, (float) $row->sas_murni);

        // Kolom Nilai Murni & input spreadsheet tampil di tab Mapel
        $this->get($url."?tab=mapel&admin_book_id={$this->book->id}&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('N. Murni')
            ->assertSee('sheet-input', false)
            ->assertSee('murniModal');
    }

    public function test_mapel_s6_otomatis_dari_formatif_dan_nilai_turunan_terupdate(): void
    {
        $this->actingAs($this->teacher);
        $sid = $this->students[0]->id;

        // Formatif (wizard 4) tersimpan dengan rata-rata 90
        \App\Models\NilaiFormatif::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $sid,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'skor_lkpd' => 90,
            'skor_diskusi' => 90,
            'skor_kuis' => 90,
            'skor_antarteman' => 90,
            'nr_final' => 90,
        ]);

        $res = $this->withHeaders(['Accept' => 'application/json'])->post(
            "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts",
            [
                'tab' => 'mapel',
                'admin_book_id' => $this->book->id,
                'nilai' => [$sid => ['sh' => ['s1' => 80]]],
            ]
        );

        $res->assertOk()->assertJson(['success' => true]);

        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(90.0, (float) $row->s6, 'S6 otomatis dari rata-rata formatif.');
        $this->assertSame(85.0, (float) $row->rs, 'RS = rata-rata S1 dan S6.');

        $json = $res->json();
        $this->assertSame(85.0, (float) $json['saved_rows'][0]['rs']);
        $this->assertArrayHasKey('rsa', $json['saved_rows'][0]);
        $this->assertArrayHasKey('nr_murni', $json['saved_rows'][0]);
        $this->assertArrayHasKey('nr_final', $json['saved_rows'][0]);
    }

    public function test_kelola_kolom_sumatif_dari_tab_mapel(): void
    {
        $this->actingAs($this->teacher);

        $columns = [
            ['id' => 's1', 'label' => 'S1'],
            ['id' => 's2', 'label' => 'S2'],
            ['id' => 's3', 'label' => 'S3'],
            ['id' => 's4', 'label' => 'S4'],
            ['id' => 's5', 'label' => 'S5'],
            ['id' => 's6', 'label' => 'S6'],
            ['id' => 'kolom-baru-1', 'label' => 'Praktik Lapangan'],
        ];

        $this->postJson("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts/kolom", [
            'admin_book_id' => $this->book->id,
            'columns' => $columns,
        ])->assertOk()->assertJson(['success' => true]);

        $saved = app(SumatifHarianService::class)->columnsFor($this->book->fresh());
        $this->assertCount(7, $saved);
        $this->assertSame('Praktik Lapangan', $saved[6]['label']);

        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts?tab=mapel&admin_book_id={$this->book->id}&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('Praktik Lapangan');
    }

    public function test_formatif_sub_kolom_rata_per_aspek_tersinkron_ke_s6(): void
    {
        $this->actingAs($this->teacher);
        $sid = $this->students[0]->id;

        $this->post("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w4", [
            'formatif' => [
                $sid => [
                    'lkpd_scores' => [80, 90, 70, 100],
                    'diskusi_scores' => [90, 90],
                    'kuis_scores' => [100, 80, 60],
                    'antarteman_scores' => [70, 70, 70, 70],
                ],
            ],
        ])->assertRedirect();

        $fmt = \App\Models\NilaiFormatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)
            ->first();

        $this->assertNotNull($fmt);
        $this->assertSame(85.0, (float) $fmt->skor_lkpd);
        $this->assertSame(90.0, (float) $fmt->skor_diskusi);
        $this->assertSame(80.0, (float) $fmt->skor_kuis);
        $this->assertSame(70.0, (float) $fmt->skor_antarteman);
        $this->assertSame(81.25, (float) $fmt->nr_final, 'NR Final = rata-rata rata aspek.');
        $this->assertCount(4, $fmt->lkpd_scores);

        $sumatif = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)
            ->first();
        $this->assertNotNull($sumatif);
        $this->assertSame(81.25, (float) $sumatif->s6, 'S6 tersinkron dari rata-rata formatif.');

        // Halaman wizard 4 menampilkan grid sub-kolom + tombol tambah kolom
        $this->get("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w4")
            ->assertOk()
            ->assertSee('data-name-prefix="formatif[', false)
            ->assertSee('tf-col-add-aspect');
    }

    public function test_kelola_kolom_formatif_dari_buku_nilai(): void
    {
        $this->actingAs($this->teacher);

        $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w4/kolom", [
            'columns' => [
                'lkpd' => ['P1', 'P2', 'P3', 'P4', 'P5'],
                'diskusi' => ['1', '2', '3', '4'],
                'kuis' => ['1', '2', '3', '4'],
                'antarteman' => ['1', '2', '3', '4'],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertCount(5, $this->book->fresh()->formatif_columns['lkpd']);

        $this->get("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w4")
            ->assertOk()
            ->assertSee('tf-col-add-aspect')
            ->assertSee('P5');
    }

    public function test_autosave_sh_tidak_menghapus_s6_dan_rs_menyertakan_s6(): void
    {
        $this->actingAs($this->teacher);
        $sid = $this->students[0]->id;

        // Formatif → S6 = 90
        \App\Models\NilaiFormatif::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $sid,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'skor_lkpd' => 90, 'skor_diskusi' => 90, 'skor_kuis' => 90, 'skor_antarteman' => 90,
            'nr_final' => 90,
        ]);

        // Autosave SH dari Buku Nilai (hanya S1) — tidak boleh menghapus S6
        $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/autosave", [
            'type' => 'sumatif',
            'sumatif' => [$sid => ['sh' => ['s1' => 80]]],
        ])->assertOk()->assertJson(['saved' => true]);

        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(90.0, (float) $row->s6, 'S6 tidak terhapus oleh autosave SH.');
        $this->assertSame(85.0, (float) $row->rs, 'RS = rata-rata S1 dan S6.');
    }

    public function test_rs_pembagi_seragam_maksimum_kolom_kelas(): void
    {
        $service = app(SumatifHarianService::class);

        // Santri 1 mengisi 4 kolom → pembagi kelas = 4
        $service->upsertSumatif($this->book, $this->students[0]->id, [
            'sh' => ['s1' => 10, 's2' => 10, 's3' => 10, 's4' => 10],
        ]);

        // Santri 2 hanya 1 kolom → tetap dibagi 4 (bukan 1)
        $rowB = $service->upsertSumatif($this->book, $this->students[1]->id, [
            'sh' => ['s1' => 80],
        ]);

        $this->assertSame(20.0, (float) $rowB->rs, 'RS santri 2 = 80 / 4 (pembagi seragam kelas).');

        $rowA = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $this->students[0]->id)
            ->first();
        $this->assertSame(10.0, (float) $rowA->rs, 'RS santri 1 = 40 / 4.');
    }

    public function test_hapus_kolom_formatif_dinomori_ulang(): void
    {
        $this->actingAs($this->teacher);

        // 5 kolom → hapus kolom ke-3 → server menomori ulang otomatis (1,2,3,4)
        $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w4/kolom", [
            'columns' => [
                'lkpd' => ['1', '2', '4', '5'],
                'diskusi' => ['1', '2', '3', '4'],
                'kuis' => ['1', '2', '3', '4'],
                'antarteman' => ['1', '2', '3', '4'],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(['1', '2', '3', '4'], $this->book->fresh()->formatif_columns['lkpd']);
    }

    public function test_presensi_buku_nilai_terlambat_dengan_keterangan(): void
    {
        $this->actingAs($this->teacher);
        $sid = $this->students[0]->id;

        $this->post("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w1", [
            'attendance_date' => now()->toDateString(),
            'status' => [$sid => 'terlambat'],
            'notes' => [$sid => 'Datang terlambat 10 menit'],
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_presensi_siswa', [
            'student_id' => $sid,
            'status' => 'terlambat',
            'notes' => 'Datang terlambat 10 menit',
        ]);

        // Desain presensi memakai kelas Velzon (btn-icon + btn-soft-*) + fungsi klik tersedia
        $this->get("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w1")
            ->assertOk()
            ->assertSee('btn-icon', false)
            ->assertSee('btn-soft-', false)
            ->assertSee('setW1Status', false)
            ->assertSee('__w1AutoSave', false)
            ->assertSee('w1-autosave-indicator', false)
            ->assertSee('w1SetAll', false)
            ->assertSee('w1-count-hadir', false);
    }

    public function test_autosave_formatif_wizard4_berhasil(): void
    {
        $this->actingAs($this->teacher);
        $sid = $this->students[0]->id;

        $res = $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/autosave", [
            'type' => 'formatif',
            'formatif' => [
                $sid => [
                    'lkpd_scores' => [80, 90],
                    'diskusi_scores' => [70, 70],
                    'kuis_scores' => [100],
                    'antarteman_scores' => [80, 80, 80],
                ],
            ],
        ]);

        $res->assertOk()->assertJson(['saved' => true]);

        $fmt = \App\Models\NilaiFormatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)->first();

        $this->assertNotNull($fmt);
        $this->assertSame(85.0, (float) $fmt->skor_lkpd);
        $this->assertSame(70.0, (float) $fmt->skor_diskusi);
        $this->assertSame(100.0, (float) $fmt->skor_kuis);
        $this->assertSame(80.0, (float) $fmt->skor_antarteman);
        // overall = (85 + 70 + 100 + 80) / 4 = 83.75
        $this->assertSame(83.75, (float) $fmt->nr_final);

        $sumatif = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $sid)->first();
        $this->assertNotNull($sumatif);
        $this->assertSame(83.75, (float) $sumatif->s6);
    }

    public function test_autosave_presensi_wizard1_json(): void
    {
        $this->actingAs($this->teacher);
        $sid = $this->students[0]->id;

        $this->postJson("/{$this->teacher->id}/schools/guru-mapel/{$this->book->id}/w1", [
            'attendance_date' => now()->toDateString(),
            'status' => [$sid => 'izin'],
            'notes' => [$sid => 'Acara keluarga'],
        ])->assertOk()->assertJson(['saved' => true]);

        $this->assertDatabaseHas('admin_presensi_siswa', [
            'student_id' => $sid,
            'status' => 'izin',
            'notes' => 'Acara keluarga',
        ]);
    }

    public function test_penyingkatan_nama_mapel_otomatis(): void
    {
        // Pendek → utuh
        $this->assertSame('Matematika', \App\Support\AcademicNilai::shortSubjectName('Matematika'));
        $this->assertSame('Bahasa Arab', \App\Support\AcademicNilai::shortSubjectName('Bahasa Arab'));

        // Panjang → diringkas per kata umum
        $this->assertSame('Haf. Hadits', \App\Support\AcademicNilai::shortSubjectName('Hafalan Hadits'));

        // Sangat panjang → dipotong rapi
        $this->assertStringEndsWith('…', \App\Support\AcademicNilai::shortSubjectName('Pendidikan Agama dan Budi Pekerti'));
    }

    public function test_autosave_leger_dan_mapel_mengembalikan_json(): void
    {
        $this->actingAs($this->teacher);

        // Persis seperti fetch() front-end: Accept JSON (tanpa X-Requested-With).
        $leger = $this->withHeaders(['Accept' => 'application/json'])->post(
            "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts",
            [
                'tab' => 'leger',
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'leger_sts' => [$this->students[0]->id => [$this->book->id => ['sts' => 82]]],
            ]
        );

        $leger->assertOk()->assertJson(['success' => true]);

        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $this->students[0]->id)
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(82.0, (float) $row->sts);

        // Autosave tab mapel
        $mapel = $this->withHeaders(['Accept' => 'application/json'])->post(
            "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts",
            [
                'tab' => 'mapel',
                'admin_book_id' => $this->book->id,
                'nilai' => [$this->students[0]->id => ['sh' => ['s1' => 80], 'sts_murni' => 85, 'sts' => 85]],
            ]
        );

        $mapel->assertOk()->assertJson(['success' => true]);
    }

    public function test_kktp_tab_leger_tersimpan_tanpa_leger_sts(): void
    {
        $this->actingAs($this->teacher);

        // Persis seperti autosave front-end tab leger saat KKTP diubah
        // (tanpa leger_sts — tabel leger kini baca-saja).
        $res = $this->withHeaders(['Accept' => 'application/json'])->post(
            "/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts",
            [
                'tab' => 'leger',
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'leger_kkm' => [$this->book->id => 88],
            ]
        );

        $res->assertOk()->assertJson(['success' => true, 'message' => 'KKTP disimpan.']);

        $row = DB::table('subject_kktp')
            ->where('subject_id', $this->subject->id)
            ->where('grade_level_id', $this->gradeLevelId)
            ->first();
        $this->assertNotNull($row, 'KKTP harus tersimpan dari tab leger walau tanpa leger_sts.');
        $this->assertEquals(88.0, (float) $row->kkm_score);
        $this->assertSame($row->id, $this->book->fresh()->kktp_id);

        // Sel KKTP (sejajar No/NIS/Nama) ikut beku horizontal
        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts?tab=leger&leger=sts&academic_year_id={$this->ay->id}&semester=ganjil")
            ->assertOk()
            ->assertSee('#tab-leger thead th[data-col="name"]', false);
    }

    public function test_semester_genap_otomatis_dibuatkan_buku(): void
    {
        $this->assertFalse(
            TeacherAdminBook::where('study_group_id', $this->studyGroup->id)
                ->where('semester', 'genap')
                ->exists(),
            'Fixture awal hanya punya buku ganjil.'
        );

        $this->actingAs($this->teacher);

        $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts?academic_year_id={$this->ay->id}&semester=genap")
            ->assertOk()
            ->assertSee('otomatis disiapkan')
            ->assertSee('Matematika');

        $this->assertTrue(
            TeacherAdminBook::where('study_group_id', $this->studyGroup->id)
                ->where('semester', 'genap')
                ->where('subject_id', $this->subject->id)
                ->exists(),
            'Buku semester genap dibuat otomatis dari semester ganjil.'
        );
    }

    public function test_cetak_rapor_massal_menghasilkan_satu_pdf(): void
    {
        $service = app(SumatifHarianService::class);

        foreach ($this->students as $student) {
            $service->upsertSumatif($this->book, $student->id, [
                'sh' => ['s1' => 80],
                'sts_murni' => 85,
                'sts' => 85,
                'sas_murni' => 88,
                'sas' => 88,
            ]);
        }

        $this->actingAs($this->teacher);

        $res = $this->get("/{$this->teacher->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/cetak-massal?academic_year_id={$this->ay->id}&semester=ganjil&jenis=sas");

        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_hak_akses_leger_wali_tu_dan_wali_lain(): void
    {
        // Rombel kedua (bakal diwalikan ke guru lain)
        $groupB = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->gradeLevelId,
            'name' => '8B',
            'code' => '8B',
            'is_active' => true,
        ]);

        $waliB = User::create([
            'name' => 'Wali Rombel B',
            'email' => 'wali.b@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $waliB->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]));
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $waliB->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $groupB->forceFill(['homeroom_teacher_id' => $waliB->id])->save();

        // Wali B: kelasnya OK, kelas lain ditolak
        $this->actingAs($waliB)
            ->get("/{$waliB->id}/schools/nilai-kelas/{$groupB->id}/sts")
            ->assertOk();

        $this->actingAs($waliB)
            ->get("/{$waliB->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts")
            ->assertStatus(403);

        // TU: semua kelas OK + index mengarah ke rombel pertama
        $tu = User::create([
            'name' => 'Staf TU Uji Leger',
            'email' => 'tu.leger@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $tu->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]));
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $tu->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Staf Tata Usaha',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($tu)
            ->get("/{$tu->id}/schools/nilai-kelas/{$this->studyGroup->id}/sts")
            ->assertOk();

        $this->actingAs($tu)
            ->get("/{$tu->id}/schools/nilai-kelas/{$groupB->id}/sts")
            ->assertOk();

        $this->actingAs($tu)
            ->get("/{$tu->id}/schools/nilai-kelas")
            ->assertRedirect();
    }
}
