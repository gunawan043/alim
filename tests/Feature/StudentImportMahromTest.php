<?php

namespace Tests\Feature;

use App\Imports\StudentImport;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Import santri — data Ayah/Ibu/Wali dari file ikut tersimpan
 * sebagai mahrom dan tidak menimpa data yang sudah ada.
 */
class StudentImportMahromTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Import',
            'code' => 'UTI',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77777777',
            'name' => 'Sekolah Uji Import',
        ]);
    }

    public function test_import_membuat_mahrom_ayah_ibu_dan_wali(): void
    {
        $import = new StudentImport($this->school->id, null);
        $import->collection($this->rows());

        $this->assertSame(1, $import->getSuccessCount());
        $this->assertSame([], $import->getErrors());
        $this->assertSame(3, $import->getMahromCount());

        $student = Student::where('nisn', '1234567890')->firstOrFail();

        $this->assertSame(3, StudentMahrom::where('student_id', $student->id)->count());

        $ayah = StudentMahrom::where('student_id', $student->id)
            ->where('relationship', 'ayah')->firstOrFail();
        $this->assertSame('Bapak Ahmad', $ayah->name);
        $this->assertSame('3201010101010001', $ayah->id_number);
        $this->assertTrue((bool) $ayah->is_primary);

        $ibu = StudentMahrom::where('student_id', $student->id)
            ->where('relationship', 'ibu')->firstOrFail();
        $this->assertSame('Ibu Siti', $ibu->name);
        $this->assertFalse((bool) $ibu->is_primary);

        $wali = StudentMahrom::where('student_id', $student->id)
            ->where('relationship', 'wali')->firstOrFail();
        $this->assertSame('Pak Wali', $wali->name);

        // Data orang tua tetap tersimpan di tabel students (kompatibilitas).
        $this->assertSame('Bapak Ahmad', $student->father_name);
        $this->assertSame('Ibu Siti', $student->mother_name);
    }

    public function test_import_ulang_hanya_melengkapi_peran_mahrom_yang_belum_ada(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad Fauzi',
            'nisn' => '1234567890',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentMahrom::create([
            'student_id' => $student->id,
            'name' => 'Bapak Ahmad',
            'relationship' => 'ayah',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $import = new StudentImport($this->school->id, null);
        $import->collection($this->rows());

        $this->assertSame(0, $import->getSuccessCount());
        $this->assertCount(1, $import->getDuplicates());
        $this->assertSame(2, $import->getMahromCount()); // ibu + wali saja

        $this->assertSame(1, Student::where('nisn', '1234567890')->count());
        $this->assertSame(1, StudentMahrom::where('student_id', $student->id)
            ->where('relationship', 'ayah')->count());
        $this->assertSame(1, StudentMahrom::where('student_id', $student->id)
            ->where('relationship', 'ibu')->count());
        $this->assertSame(1, StudentMahrom::where('student_id', $student->id)
            ->where('relationship', 'wali')->count());
    }

    /**
     * @return Collection<int, Collection<int, string>>
     */
    private function rows(): Collection
    {
        $data = array_fill(0, 66, '');
        $data[1] = 'Ahmad Fauzi';
        $data[2] = 'S-001';
        $data[3] = 'L';
        $data[4] = '1234567890';
        $data[6] = '2010-01-01';
        $data[7] = '3201010101010009';
        $data[9] = 'Jl. Pesantren No. 1';

        // Ayah
        $data[24] = 'Bapak Ahmad';
        $data[25] = '1980';
        $data[26] = 'SMA';
        $data[27] = 'Petani';
        $data[29] = '3201010101010001';

        // Ibu
        $data[30] = 'Ibu Siti';
        $data[31] = '1982';
        $data[32] = 'SMA';
        $data[33] = 'Ibu Rumah Tangga';
        $data[35] = '3201010101010002';

        // Wali
        $data[36] = 'Pak Wali';
        $data[37] = '1975';
        $data[38] = 'S1';
        $data[39] = 'Guru';
        $data[41] = '3201010101010003';

        return collect([
            collect([]), collect([]), collect([]), collect([]), collect([]),
            collect($data),
        ]);
    }
}
