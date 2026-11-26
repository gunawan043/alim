<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Support\StudentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Urutan daftar santri: satu sumber untuk seluruh modul.
 */
class UrutSantriTest extends TestCase
{
    use RefreshDatabase;

    private function school(): School
    {
        return School::factory()->create();
    }

    private function student(School $school, string $name, ?string $nis): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => $name,
            'nis' => $nis,
            'nisn' => (string) random_int(1000000000, 9999999999),
            'gender' => 'L',
            'status' => 'active',
        ]);
    }

    public function test_bawaan_sekolah_adalah_nama(): void
    {
        $school = $this->school();

        $this->assertSame(StudentOrder::NAME, StudentOrder::defaultFor($school->id));
    }

    public function test_bawaan_sekolah_tersimpan_dan_terbaca(): void
    {
        $school = $this->school();

        StudentOrder::saveDefault($school->id, StudentOrder::NIS);

        $this->assertSame(StudentOrder::NIS, StudentOrder::defaultFor($school->id));
        $this->assertSame(
            StudentOrder::NIS,
            SystemSetting::get(StudentOrder::settingKey($school->id))
        );
    }

    public function test_bawaan_berbeda_antar_sekolah(): void
    {
        $a = $this->school();
        $b = $this->school();

        StudentOrder::saveDefault($a->id, StudentOrder::NIS);

        $this->assertSame(StudentOrder::NIS, StudentOrder::defaultFor($a->id));
        $this->assertSame(StudentOrder::NAME, StudentOrder::defaultFor($b->id));
    }

    public function test_nilai_tidak_dikenal_diabaikan(): void
    {
        $school = $this->school();

        StudentOrder::saveDefault($school->id, 'ngawur');

        $this->assertSame(StudentOrder::NAME, StudentOrder::defaultFor($school->id));
        $this->assertFalse(StudentOrder::isAllowed('ngawur'));
    }

    public function test_urutan_nis_memakai_angka_bukan_teks(): void
    {
        $school = $this->school();
        $this->student($school, 'Sepuluh', '10');
        $this->student($school, 'Sembilan', '9');
        $this->student($school, 'Seratus', '100');
        $this->student($school, 'Dua', '2');

        $names = Student::query()->preferredOrder(StudentOrder::NIS)->pluck('name')->all();

        // Teks biasa akan menghasilkan 10, 100, 2, 9.
        $this->assertSame(['Dua', 'Sembilan', 'Sepuluh', 'Seratus'], $names);
    }

    public function test_santri_tanpa_nis_di_akhir_lalu_urut_nama(): void
    {
        $school = $this->school();
        $this->student($school, 'Zaki', '3');
        $this->student($school, 'Ahmad', null);
        $this->student($school, 'Budi', null);
        $this->student($school, 'Ani', '');

        $names = Student::query()->preferredOrder(StudentOrder::NIS)->pluck('name')->all();

        $this->assertSame(['Zaki', 'Ahmad', 'Ani', 'Budi'], $names);
    }

    public function test_urutan_nama_tetap_perilaku_lama(): void
    {
        $school = $this->school();
        $this->student($school, 'Citra', '9');
        $this->student($school, 'Andi', '10');
        $this->student($school, 'Budi', '2');

        $names = Student::query()->preferredOrder(StudentOrder::NAME)->pluck('name')->all();

        $this->assertSame(['Andi', 'Budi', 'Citra'], $names);
    }

    public function test_sort_pada_request_mengalahkan_bawaan_sekolah(): void
    {
        $school = $this->school();
        StudentOrder::saveDefault($school->id, StudentOrder::NIS);

        $override = \Illuminate\Http\Request::create('/x?sort=name');

        $this->assertSame(StudentOrder::NAME, StudentOrder::resolve($override, $school->id));
        // Tanpa ?sort=, bawaan sekolah yang dipakai.
        $this->assertSame(StudentOrder::NIS, StudentOrder::resolve(\Illuminate\Http\Request::create('/x'), $school->id));
        // Nilai ?sort= yang tidak dikenal tidak boleh membatalkan bawaan.
        $this->assertSame(StudentOrder::NIS, StudentOrder::resolve(\Illuminate\Http\Request::create('/x?sort=ngawur'), $school->id));
    }

    public function test_angka_diambil_dari_nis_berawalan_huruf(): void
    {
        // "AMPi 024010" → 24010, "AMPi 024002" → 24002.
        $a = StudentOrder::numericKey('AMPi 024002')[0];
        $b = StudentOrder::numericKey('AMPi 024010')[0];

        $this->assertLessThan($b, $a);
        $this->assertSame(24002.0, $a);
        $this->assertSame([24001.0], StudentOrder::numericRuns('AMPi 024001'));
    }

    public function test_tahun_berbeda_tidak_bertabrakan(): void
    {
        $school = $this->school();
        $this->student($school, 'Angkatan 24', 'AMPi 024001');
        $this->student($school, 'Angkatan 26', 'AMPi 026001');
        $this->student($school, 'Angkatan 25', 'AMPi 025001');

        $names = Student::query()->preferredOrder(StudentOrder::NIS)->pluck('name')->all();

        $this->assertSame(['Angkatan 24', 'Angkatan 25', 'Angkatan 26'], $names);
    }

    public function test_sort_koleksi_mengikuti_aturan_yang_sama(): void
    {
        $school = $this->school();
        $rows = collect([
            ['student' => $this->student($school, 'Kosong', null)],
            ['student' => $this->student($school, 'Sepuluh', '10')],
            ['student' => $this->student($school, 'Dua', '2')],
        ]);

        $sorted = StudentOrder::sortCollection($rows, fn ($r) => $r['student'], StudentOrder::NIS)
            ->map(fn ($r) => $r['student']->name)
            ->all();

        $this->assertSame(['Dua', 'Sepuluh', 'Kosong'], $sorted);
    }
}
