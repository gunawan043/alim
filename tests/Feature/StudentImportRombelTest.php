<?php

namespace Tests\Feature;

use App\Events\StudentAssignedToRombel;
use App\Exports\StudentTemplateExport;
use App\Imports\StudentImport;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class StudentImportRombelTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([StudentAssignedToRombel::class]);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Import', 'code' => 'UIM',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '92929292', 'name' => 'Sekolah Import',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 11, 'name' => 'Kelas 11', 'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Operator Import', 'email' => 'operator.import@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $this->actingAs($this->user);
    }

    public function test_template_export_kompatibel_dengan_maatwebsite_excel_v4(): void
    {
        $export = new StudentTemplateExport($this->school->id);
        $headings = $export->headings()[0];

        $this->assertInstanceOf(Export::class, $export);
        $this->assertCount(66, $headings);
        $this->assertSame('Rombel Saat Ini', $headings[42]);
        $this->assertSame('No Peserta Ujian Nasional', $headings[43]);
        $this->assertSame('Jarak Rumah ke Sekolah (KM)', $headings[65]);
        $this->assertStringStartsWith('PK', Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
    }

    public function test_import_file_dapodik_dengan_kolom_rombel_tambahan_dan_override(): void
    {
        $target = $this->makeGroup('XI A');

        $rows = $this->dapodikRows([
            'name' => 'Ahmad Pindahan',
            'nisn' => '9900112233',
            'rombel' => '9A',
            'ujian' => 'UN-123',
            'ijazah' => 'IJ-123',
        ]);

        $import = new StudentImport($this->school->id, $target->id);
        $import->collection(collect($rows));

        $this->assertSame(1, $import->getSuccessCount(), json_encode($import->getErrors()));
        $this->assertSame([], $import->getErrors());

        $student = \App\Models\Student::where('nisn', '9900112233')->firstOrFail();
        $this->assertSame('UN-123', $student->ujian_national_number);
        $this->assertSame('IJ-123', $student->certificate_number);

        $this->assertDatabaseHas('student_class_histories', [
            'student_id' => $student->id,
            'study_group_id' => $target->id,
            'academic_year_id' => $this->ay->id,
        ]);
    }

    public function test_import_mencocokkan_9a_dengan_ix_a(): void
    {
        $target = $this->makeGroup('IX A');

        $rows = $this->dapodikRows([
            'name' => 'Siti Format',
            'nisn' => '9900112244',
            'rombel' => '9A',
        ]);

        $import = new StudentImport($this->school->id);
        $import->collection(collect($rows));

        $this->assertSame(1, $import->getSuccessCount(), json_encode($import->getErrors()));

        $student = \App\Models\Student::where('nisn', '9900112244')->firstOrFail();
        $this->assertDatabaseHas('student_class_histories', [
            'student_id' => $student->id,
            'study_group_id' => $target->id,
        ]);
    }

    public function test_import_template_baru_dengan_rombel_di_kolom_terakhir(): void
    {
        $target = $this->makeGroup('XI B');

        $rows = $this->dapodikRows([
            'name' => 'Template Baru',
            'nisn' => '9900112255',
            'rombel' => 'XI B',
            'header_row' => 3,
            'data_row' => 5,
            'rombel_at' => 65,
        ]);

        $import = new StudentImport($this->school->id);
        $import->collection(collect($rows));

        $this->assertSame(1, $import->getSuccessCount(), json_encode($import->getErrors()));

        $student = \App\Models\Student::where('nisn', '9900112255')->firstOrFail();
        $this->assertDatabaseHas('student_class_histories', [
            'student_id' => $student->id,
            'study_group_id' => $target->id,
        ]);
    }


    public function test_import_ulang_tidak_menggandakan_santri(): void
    {
        $this->makeGroup('XI C');
        $rows = $this->dapodikRows([
            'name' => 'Duplikat Uji',
            'nisn' => '9900112266',
            'rombel' => 'XI C',
        ]);

        $first = new StudentImport($this->school->id);
        $first->collection(collect($rows));
        $this->assertSame(1, $first->getSuccessCount(), json_encode($first->getErrors()));

        $second = new StudentImport($this->school->id);
        $second->collection(collect($rows));

        $this->assertSame(0, $second->getSuccessCount(), 'Import kedua tidak boleh membuat santri baru.');
        $this->assertCount(1, $second->getDuplicates());
        $this->assertSame(1, \App\Models\Student::where('nisn', '9900112266')->count());
    }

    public function test_transportasi_lainnya_tidak_menggagalkan_baris(): void
    {
        $this->makeGroup('XI D');
        $rows = $this->dapodikRows([
            'name' => 'Transport Uji',
            'nisn' => '9900112277',
            'rombel' => 'XI D',
            'transport' => 'Lainnya',
        ]);

        $import = new StudentImport($this->school->id);
        $import->collection(collect($rows));

        $this->assertSame(1, $import->getSuccessCount(), json_encode($import->getErrors()));
        $this->assertDatabaseHas('students', [
            'nisn' => '9900112277',
            'transportation' => 'jalan_kaki',
        ]);
    }

    private function makeGroup(string $name): StudyGroup
    {
        return StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => $name,
            'code' => $name,
            'capacity' => 40,
            'is_active' => true,
        ]);
    }

    /**
     * Baris Dapodik: 4 baris info, header, subheader, data.
     */
    private function dapodikRows(array $data): array
    {
        $headerRow = $data['header_row'] ?? 4;
        $dataRow = $data['data_row'] ?? 6;
        $rombelAt = $data['rombel_at'] ?? 42;
        $rowCount = max($dataRow, $headerRow + 1) + 1;
        $rows = array_fill(0, $rowCount, array_fill(0, 66, ''));

        $rows[0][0] = 'Daftar Peserta Didik';
        $rows[$headerRow][1] = 'Nama';
        $rows[$headerRow][2] = 'NIPD';
        $rows[$headerRow][3] = 'JK';
        $rows[$headerRow][4] = 'NISN';
        $rows[$headerRow][$rombelAt] = 'Rombel Saat Ini';
        $rows[$headerRow][$rombelAt + 1] = 'No Peserta Ujian Nasional';
        $rows[$headerRow][$rombelAt + 2] = 'No Seri Ijazah';

        // Sub-header orang tua (posisi tetap sesuai template ALIM).
        if ($headerRow + 1 < $rowCount) {
            foreach ([24, 30, 36] as $start) {
                foreach (['Nama', 'Tahun Lahir', 'Jenjang Pendidikan', 'Pekerjaan', 'Penghasilan', 'NIK'] as $offset => $label) {
                    $rows[$headerRow + 1][$start + $offset] = $label;
                }
            }
        }

        $rows[$dataRow][1] = $data['name'];
        $rows[$dataRow][2] = 'NIPD-'.$data['nisn'];
        $rows[$dataRow][3] = 'L';
        $rows[$dataRow][4] = $data['nisn'];
        $rows[$dataRow][5] = 'Mataram';
        $rows[$dataRow][6] = '2010-01-01';
        $rows[$dataRow][17] = $data['transport'] ?? 'Jalan Kaki';
        $rows[$dataRow][24] = 'Ayah '.$data['name'];
        $rows[$dataRow][30] = 'Ibu '.$data['name'];
        $rows[$dataRow][$rombelAt] = $data['rombel'];
        $rows[$dataRow][$rombelAt + 1] = $data['ujian'] ?? '';
        $rows[$dataRow][$rombelAt + 2] = $data['ijazah'] ?? '';

        return $rows;
    }
}
