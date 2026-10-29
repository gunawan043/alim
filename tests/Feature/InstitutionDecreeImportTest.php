<?php

namespace Tests\Feature;

use App\Imports\InstitutionDecreeImport;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InstitutionDecreeImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $group;

    private Subject $subject;

    private User $teacher;

    private User $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Import SK',
            'code' => 'UISK',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99999999',
            'name' => 'Sekolah Uji Import SK',
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
            'code' => 'VII',
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
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad.import@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->importer = User::create([
            'name' => 'Admin Import',
            'email' => 'admin.import@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->teacher->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'nupy' => '1234567890',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Mapel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_import_membuat_sk_matriks_tugas_buku_admin_dan_snapshot(): void
    {
        $result = $this->importWorkbook($this->workbook(
            [
                ['nomor_sk' => '001/SK/2026', 'judul' => 'Pembagian Tugas Mengajar', 'tahun_ajaran' => '2026/2027', 'tanggal_ditetapkan' => '2026-07-01', 'status' => 'active'],
            ],
            [
                ['nomor_sk' => '001/SK/2026', 'nupy' => '1234567890', 'nama_guru' => 'Ahmad Fauzi', 'kode_mapel' => 'MAT', 'mapel' => 'Matematika', 'kelas' => '7', 'rombel' => '7A', 'jp' => 6],
            ],
            [
                ['nomor_sk' => '001/SK/2026', 'nupy' => '1234567890', 'nama_guru' => 'Ahmad Fauzi', 'nama_tugas' => 'Wali Kelas', 'kode_tugas' => 'wali-kelas', 'rombel' => '7A', 'jp' => 12],
            ]
        ));

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertEmpty($result['errors']);

        $decree = InstitutionDecree::where('decree_number', '001/SK/2026')->firstOrFail();
        $this->assertSame($this->school->id, $decree->school_id);
        $this->assertSame('active', $decree->status);
        $this->assertNotEmpty($decree->matrix_snapshot);
        $this->assertNotNull($decree->matrix_snapshot_at);

        $this->assertDatabaseHas('teaching_assignments', [
            'decree_id' => $decree->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'weekly_hours' => 6,
        ]);

        $this->assertDatabaseHas('other_teacher_tasks', [
            'decree_id' => $decree->id,
            'teacher_id' => $this->teacher->id,
            'task_code' => 'wali-kelas',
            'weekly_hours' => 12,
        ]);

        $this->assertDatabaseHas('teacher_admin_books', [
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ]);

        $snapshot = json_decode($decree->matrix_snapshot, true);
        $this->assertSame('001/SK/2026', $snapshot['decree_number']);
        $this->assertSame('Ahmad Fauzi', $snapshot['rows'][0]['teacher_name']);
        $this->assertSame('Wali Kelas', $snapshot['rows'][0]['tasks'][0]['name']);
    }

    public function test_import_menolak_nomor_sk_duplikat_tanpa_opsi_perbarui(): void
    {
        $rows = [
            ['nomor_sk' => '002/SK/2026', 'judul' => 'Pembagian Tugas', 'tahun_ajaran' => '2026/2027', 'tanggal_ditetapkan' => '2026-07-01'],
        ];
        $matrix = [
            ['nomor_sk' => '002/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'mapel' => 'Matematika', 'rombel' => '7A', 'jp' => 6],
        ];

        $first = $this->importWorkbook($this->workbook($rows, $matrix));
        $this->assertSame(1, $first['created']);

        $second = $this->importWorkbook($this->workbook($rows, $matrix));
        $this->assertSame(0, $second['created']);
        $this->assertNotEmpty($second['errors']);
        $this->assertStringContainsString('sudah terdaftar', $second['errors'][0]);
        $this->assertSame(1, InstitutionDecree::where('decree_number', '002/SK/2026')->count());
    }

    public function test_import_perbarui_menghapus_baris_dan_tugas_yang_tidak_ada_lagi(): void
    {
        $baseRows = [
            ['nomor_sk' => '003/SK/2026', 'judul' => 'Pembagian Tugas', 'tahun_ajaran' => '2026/2027', 'tanggal_ditetapkan' => '2026-07-01'],
        ];

        $first = $this->importWorkbook($this->workbook(
            $baseRows,
            [
                ['nomor_sk' => '003/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'mapel' => 'Matematika', 'rombel' => '7A', 'jp' => 6],
            ],
            [
                ['nomor_sk' => '003/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'nama_tugas' => 'Wali Kelas', 'kode_tugas' => 'wali-kelas', 'rombel' => '7A', 'jp' => 12],
            ]
        ));
        $this->assertSame(1, $first['created']);

        $decree = InstitutionDecree::where('decree_number', '003/SK/2026')->firstOrFail();

        // Workbook kedua: matriks kosong dan sheet TUGAS ada tetapi kosong.
        $second = $this->importWorkbook($this->workbook($baseRows, [], []), true);

        $this->assertSame(1, $second['updated']);
        $this->assertSame(0, $decree->teachingAssignments()->count());
        $this->assertSame(0, \App\Models\OtherTeacherTask::where('decree_id', $decree->id)->count());
        $this->assertDatabaseMissing('teaching_assignments', ['decree_id' => $decree->id]);
        $this->assertDatabaseMissing('other_teacher_tasks', ['decree_id' => $decree->id]);
    }

    public function test_template_import_dapat_dibuat(): void
    {
        $response = (new \App\Exports\InstitutionDecreeTemplateExport($this->school, $this->ay))
            ->download('template-test-sk.xlsx');

        $path = $response->getFile()->getPathname();

        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path));

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $titles = collect($spreadsheet->getAllSheets())->map(fn ($sheet) => $sheet->getTitle())->all();

        $this->assertContains('SK', $titles);
        $this->assertContains('MATRIKS', $titles);
        $this->assertContains('TUGAS', $titles);
        $this->assertSame('Nomor SK *', $spreadsheet->getSheetByName('SK')->getCell('A1')->getValue());

        $response->deleteFileAfterSend(false);
        @unlink($path);
    }

    public function test_akses_sk_lintas_sekolah_ditolak(): void
    {
        $decree = InstitutionDecree::create([
            'decree_number' => '004/SK/2026',
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'SK Sekolah Lain',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => 'active',
        ]);

        $otherSchool = School::create([
            'work_unit_id' => $this->school->work_unit_id,
            'npsn' => '88888888',
            'name' => 'Sekolah Lain',
        ]);

        $this->actingAs($this->importer);

        $request = \Illuminate\Http\Request::create('/'.$this->importer->id.'/institution-decrees/'.$decree->id, 'GET');
        $request->attributes->set('schoolContextId', $otherSchool->id);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(\App\Http\Controllers\InstitutionDecreeController::class)
            ->show($request, $this->importer->id, $decree->id);
    }

    public function test_dua_sk_boleh_memiliki_tugas_tambahan_yang_sama(): void
    {
        $result = $this->importWorkbook($this->workbook(
            [
                ['nomor_sk' => '005/SK/2026', 'judul' => 'SK Ganjil', 'tahun_ajaran' => '2026/2027', 'tanggal_ditetapkan' => '2026-07-01'],
                ['nomor_sk' => '006/SK/2026', 'judul' => 'SK Genap', 'tahun_ajaran' => '2026/2027', 'tanggal_ditetapkan' => '2026-07-01'],
            ],
            [
                ['nomor_sk' => '005/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'mapel' => 'Matematika', 'rombel' => '7A', 'jp' => 6],
                ['nomor_sk' => '006/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'mapel' => 'Matematika', 'rombel' => '7A', 'jp' => 6],
            ],
            [
                ['nomor_sk' => '005/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'nama_tugas' => 'Wali Kelas', 'kode_tugas' => 'wali-kelas', 'rombel' => '7A', 'jp' => 12],
                ['nomor_sk' => '006/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'nama_tugas' => 'Wali Kelas', 'kode_tugas' => 'wali-kelas', 'rombel' => '7A', 'jp' => 12],
            ]
        ));

        $this->assertSame(2, $result['created']);
        $this->assertEmpty($result['errors']);
        $this->assertSame(
            2,
            \App\Models\OtherTeacherTask::where('teacher_id', $this->teacher->id)
                ->where('task_code', 'wali-kelas')
                ->count()
        );
    }

    public function test_snapshot_tidak_berubah_walau_data_master_diperbarui(): void
    {
        $this->importWorkbook($this->workbook(
            [['nomor_sk' => '007/SK/2026', 'judul' => 'SK Snapshot', 'tahun_ajaran' => '2026/2027', 'tanggal_ditetapkan' => '2026-07-01']],
            [['nomor_sk' => '007/SK/2026', 'nama_guru' => 'Ahmad Fauzi', 'mapel' => 'Matematika', 'rombel' => '7A', 'jp' => 6]]
        ));

        $decree = InstitutionDecree::where('decree_number', '007/SK/2026')->firstOrFail();
        $this->teacher->update(['name' => 'Nama Guru Berubah']);

        $service = app(\App\Services\InstitutionDecreeMatrixService::class);
        $snapshot = $service->renderData($decree->fresh(), true);
        $live = $service->renderData($decree->fresh(), false);

        $this->assertSame('Ahmad Fauzi', $snapshot['matrixRows'][0]['teacher_name']);
        $this->assertSame('Nama Guru Berubah', $live['matrixRows'][0]['teacher_name']);
    }

    private function importWorkbook(UploadedFile $file, bool $updateExisting = false): array
    {
        return (new InstitutionDecreeImport($this->school, $this->ay, $updateExisting, $this->importer->id))
            ->import($file);
    }

    private function workbook(array $skRows, array $matrixRows = [], array $taskRows = []): UploadedFile
    {
        $spreadsheet = new Spreadsheet;

        $sk = $spreadsheet->getActiveSheet()->setTitle('SK');
        $sk->fromArray([
            ['Nomor SK *', 'Jenis SK', 'Judul *', 'Deskripsi', 'Tahun Ajaran', 'Tanggal Ditetapkan *', 'Tanggal Efektif', 'Tanggal Berakhir', 'Penandatangan NUPY', 'Penandatangan Nama', 'Jabatan Penandatangan', 'Status'],
        ], null, 'A1');
        foreach ($skRows as $index => $row) {
            $sk->fromArray([[
                $row['nomor_sk'] ?? '',
                $row['jenis_sk'] ?? 'SK Pembagian Tugas',
                $row['judul'] ?? '',
                $row['deskripsi'] ?? '',
                $row['tahun_ajaran'] ?? '',
                $row['tanggal_ditetapkan'] ?? '',
                $row['tanggal_efektif'] ?? '',
                $row['tanggal_berakhir'] ?? '',
                $row['penandatangan_nupy'] ?? '',
                $row['penandatangan_nama'] ?? '',
                $row['jabatan_penandatangan'] ?? '',
                $row['status'] ?? 'draft',
            ]], null, 'A'.($index + 2));
        }

        $matrix = $spreadsheet->createSheet()->setTitle('MATRIKS');
        $matrix->fromArray([
            ['Nomor SK *', 'NUPY', 'Nama Guru *', 'Kode Mapel', 'Mapel *', 'Kelas', 'Rombel *', 'JP *', 'Koordinator'],
        ], null, 'A1');
        foreach ($matrixRows as $index => $row) {
            $matrix->fromArray([[
                $row['nomor_sk'] ?? '',
                $row['nupy'] ?? '',
                $row['nama_guru'] ?? '',
                $row['kode_mapel'] ?? '',
                $row['mapel'] ?? '',
                $row['kelas'] ?? '',
                $row['rombel'] ?? '',
                $row['jp'] ?? '',
                $row['koordinator'] ?? '',
            ]], null, 'A'.($index + 2));
        }

        $tasks = $spreadsheet->createSheet()->setTitle('TUGAS');
        $tasks->fromArray([
            ['Nomor SK *', 'NUPY', 'Nama Guru *', 'Nama Tugas *', 'Kode Tugas', 'Rombel', 'JP'],
        ], null, 'A1');
        foreach ($taskRows as $index => $row) {
            $tasks->fromArray([[
                $row['nomor_sk'] ?? '',
                $row['nupy'] ?? '',
                $row['nama_guru'] ?? '',
                $row['nama_tugas'] ?? '',
                $row['kode_tugas'] ?? '',
                $row['rombel'] ?? '',
                $row['jp'] ?? '',
            ]], null, 'A'.($index + 2));
        }

        $path = tempnam(sys_get_temp_dir(), 'sk-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'sk-import.xlsx', null, null, true);
    }
}
