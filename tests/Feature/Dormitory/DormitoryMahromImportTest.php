<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Imports\StudentMahromImport;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Modul Import Data Mahrom:
 * template, validasi baris, upsert per peran, dan scope akses.
 */
class DormitoryMahromImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Student $student;

    private Student $otherStudent;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Import Mahrom', 'code' => 'UU-IM',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '44332211',
            'name' => 'Sekolah Import Mahrom',
            'boarding_type' => 'boarding',
        ]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '1111000001',
            'nis' => 'S-0001',
            'name' => 'Santri Import Satu',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->otherStudent = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '1111000002',
            'nis' => 'S-0002',
            'name' => 'Santri Import Dua',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
    }

    private function row(array $overrides = []): \Illuminate\Support\Collection
    {
        return collect(array_merge([
            'nisn' => $this->student->nisn,
            'nis' => '',
            'nama_mahrom' => 'Bapak Import',
            'hubungan' => 'ayah',
            'no_hp' => '0812 3456 7890',
            'nik' => '',
            'alamat' => 'Jl. Import No. 1',
            'utama' => 'ya',
            'aktif' => 'ya',
            'catatan' => '',
        ], $overrides));
    }

    private function runImport(array $rows, ?array $allowedStudentIds = null): StudentMahromImport
    {
        $import = new StudentMahromImport($this->school->id, $allowedStudentIds);
        $import->collection(collect($rows));

        return $import;
    }

    public function test_import_membuat_mahrom_baru_dengan_nomor_ternormalisasi(): void
    {
        $import = $this->runImport([$this->row()]);

        $this->assertSame(1, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame([], $import->errors);

        $mahrom = StudentMahrom::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame('ayah', $mahrom->relationship);
        $this->assertSame('Bapak Import', $mahrom->name);
        $this->assertSame('081234567890', $mahrom->phone);
        $this->assertTrue((bool) $mahrom->is_primary);
    }

    public function test_import_memperbarui_peran_yang_sudah_ada_tanpa_duplikasi(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Nama Lama',
            'relationship' => 'ayah',
            'phone' => null,
            'is_active' => true,
            'is_primary' => true,
        ]);

        $import = $this->runImport([$this->row(['nama_mahrom' => 'Nama Baru'])]);

        $this->assertSame(0, $import->created);
        $this->assertSame(1, $import->updated);
        $this->assertSame(1, StudentMahrom::where('student_id', $this->student->id)->count());

        $mahrom = StudentMahrom::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('Nama Baru', $mahrom->name);
        $this->assertSame('081234567890', $mahrom->phone);
    }

    public function test_import_tidak_menghapus_nomor_lama_saat_kolom_kosong(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Lama',
            'relationship' => 'ayah',
            'phone' => '081299999999',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $import = $this->runImport([$this->row(['no_hp' => '', 'alamat' => ''])]);

        $this->assertSame(1, $import->updated);
        $this->assertSame('081299999999', StudentMahrom::where('student_id', $this->student->id)->value('phone'));
    }

    public function test_import_melewati_santri_tidak_dikenal(): void
    {
        $import = $this->runImport([$this->row(['nisn' => '9999999999', 'nis' => '']) ]);

        $this->assertSame(0, $import->created);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('tidak ditemukan', $import->errors[0]);
    }

    public function test_import_menghormati_batas_maksimal_mahrom(): void
    {
        foreach (['ayah', 'ibu', 'wali', 'kakak'] as $rel) {
            StudentMahrom::create([
                'id' => (string) Str::uuid(),
                'student_id' => $this->student->id,
                'name' => 'Mahrom '.$rel,
                'relationship' => $rel,
                'is_active' => true,
                'is_primary' => $rel === 'ayah',
            ]);
        }

        $import = $this->runImport([$this->row(['hubungan' => 'paman', 'nama_mahrom' => 'Pak Paman'])]);

        $this->assertSame(0, $import->created);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('Maksimal', $import->errors[0]);
    }

    public function test_import_menerima_alias_hubungan(): void
    {
        $import = $this->runImport([$this->row(['hubungan' => 'Bapak'])]);

        $this->assertSame(1, $import->created);
        $this->assertSame('ayah', StudentMahrom::where('student_id', $this->student->id)->value('relationship'));
    }

    public function test_import_menghormati_scope_santri_wali_kelas(): void
    {
        $import = $this->runImport([
            $this->row(['nisn' => $this->otherStudent->nisn, 'nama_mahrom' => 'Tidak Boleh']),
        ], [$this->student->id]);

        $this->assertSame(0, $import->created);
        $this->assertSame(1, $import->skipped);
        $this->assertSame(0, StudentMahrom::where('student_id', $this->otherStudent->id)->count());
    }

    public function test_halaman_import_dan_template_dapat_diakses(): void
    {
        $url = route('user.students.mahroms.import', ['userId' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->get($url)
            ->assertOk()
            ->assertSee('Import Data Mahrom')
            ->assertSee('Unduh Data Mahrom (Terisi Otomatis)')
            ->assertSee('Template Kosong');

        $this->actingAs($this->admin)
            ->get(route('user.students.mahroms.import.template', ['userId' => $this->admin->id]))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($this->admin)
            ->get(route('user.students.mahroms.import.template-filled', ['userId' => $this->admin->id]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_unggah_file_excel_membuat_mahrom(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['NISN', 'NIS', 'Nama Mahrom', 'Hubungan', 'No. HP', 'NIK', 'Alamat', 'Utama', 'Aktif', 'Catatan'],
            [$this->student->nisn, '', 'Bapak Upload', 'ayah', '081277778888', '', 'Jl. Upload', 'ya', 'ya', ''],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'mahrom_import_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'mahrom.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->actingAs($this->admin)
            ->post(route('user.students.mahroms.import.store', ['userId' => $this->admin->id]), [
                'file' => $file,
            ])
            ->assertRedirect(route('user.students.mahroms.import', ['userId' => $this->admin->id]));

        $this->assertSame('Bapak Upload', StudentMahrom::where('student_id', $this->student->id)->value('name'));
        $this->assertSame('081277778888', StudentMahrom::where('student_id', $this->student->id)->value('phone'));

        @unlink($path);
    }

    public function test_tombol_import_tampil_di_halaman_data_mahrom(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user.students.mahroms.global', ['userId' => $this->admin->id]))
            ->assertOk()
            ->assertSee('Import Mahrom');
    }

    public function test_import_format_lebar_satu_baris_untuk_banyak_mahrom(): void
    {
        $import = $this->runImport([
            collect([
                'nisn' => $this->student->nisn,
                'nis' => '',
                'nama_santri' => $this->student->name,
                'kelas' => 'Kelas 7 A',
                'kamar' => 'Kamar 1',
                'asrama' => 'Asrama Uji',
                'mahrom_1_nama' => 'Bapak Lebar',
                'mahrom_1_hubungan' => 'ayah',
                'mahrom_1_no_hp' => '081211112222',
                'mahrom_1_nik' => '',
                'mahrom_1_alamat' => 'Jl. Lebar',
                'mahrom_1_utama' => 'ya',
                'mahrom_2_nama' => 'Ibu Lebar',
                'mahrom_2_hubungan' => 'ibu',
                'mahrom_2_no_hp' => '081233334444',
                'mahrom_2_nik' => '',
                'mahrom_2_alamat' => '',
                'mahrom_2_utama' => 'tidak',
                'mahrom_3_nama' => '',
                'mahrom_3_hubungan' => '',
            ]),
        ]);

        $this->assertSame(2, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame(1, \App\Models\Student::where('id', $this->student->id)->count(), 'Santri tidak terduplikasi.');
        $this->assertSame(2, StudentMahrom::where('student_id', $this->student->id)->count());

        $ayah = StudentMahrom::where('student_id', $this->student->id)->where('relationship', 'ayah')->firstOrFail();
        $ibu = StudentMahrom::where('student_id', $this->student->id)->where('relationship', 'ibu')->firstOrFail();

        $this->assertSame('Bapak Lebar', $ayah->name);
        $this->assertSame('081211112222', $ayah->phone);
        $this->assertTrue((bool) $ayah->is_primary);
        $this->assertSame('Ibu Lebar', $ibu->name);
        $this->assertSame('081233334444', $ibu->phone);
        $this->assertFalse((bool) $ibu->is_primary);
    }

    public function test_import_format_lebar_memperbarui_mahrom_lama_tanpa_duplikasi(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Nama Lama',
            'relationship' => 'ayah',
            'phone' => '081200000000',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $import = $this->runImport([
            collect([
                'nisn' => $this->student->nisn,
                'nis' => '',
                'mahrom_1_nama' => 'Nama Baru',
                'mahrom_1_hubungan' => 'ayah',
                'mahrom_1_no_hp' => '081255556666',
                'mahrom_1_nik' => '',
                'mahrom_1_alamat' => '',
                'mahrom_1_utama' => 'ya',
                'mahrom_2_nama' => 'Ibu Baru',
                'mahrom_2_hubungan' => 'ibu',
                'mahrom_2_no_hp' => '081277778888',
                'mahrom_2_nik' => '',
                'mahrom_2_alamat' => '',
                'mahrom_2_utama' => 'tidak',
            ]),
        ]);

        $this->assertSame(1, $import->updated);
        $this->assertSame(1, $import->created);
        $this->assertSame(2, StudentMahrom::where('student_id', $this->student->id)->count());

        $ayah = StudentMahrom::where('student_id', $this->student->id)->where('relationship', 'ayah')->firstOrFail();
        $this->assertSame('Nama Baru', $ayah->name);
        $this->assertSame('081255556666', $ayah->phone);
    }

    public function test_template_terisi_memuat_informasi_santri_dan_mahrom(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Template',
            'relationship' => 'ayah',
            'phone' => '081299990000',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('user.students.mahroms.import.template-filled', ['userId' => $this->admin->id])
        );

        $response->assertOk()->assertDownload();

        $path = $response->baseResponse->getFile()->getPathname();
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getSheet(0);

        $this->assertSame('NISN', $sheet->getCell('A1')->getValue());
        $this->assertSame('Mahrom 1 - Nama', $sheet->getCell('G1')->getValue());

        $row = null;
        foreach ($sheet->toArray(null, true, true, true) as $candidate) {
            if ((string) ($candidate['A'] ?? '') === $this->student->nisn) {
                $row = $candidate;
                break;
            }
        }

        $this->assertNotNull($row, 'Baris santri harus terisi otomatis.');
        $this->assertSame($this->student->name, $row['C']);
        $this->assertSame('Bapak Template', $row['G']);
        $this->assertSame('ayah', strtolower((string) $row['H']));
        $this->assertSame('081299990000', $row['I']);
        $this->assertSame('ya', $row['L']);
    }
}
