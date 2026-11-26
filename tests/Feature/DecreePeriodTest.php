<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Services\DecreePeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Semester data nilai ditentukan SK pembagian tugas, bukan academic_years.semester.
 */
class DecreePeriodTest extends TestCase
{
    use RefreshDatabase;

    private function school(): School
    {
        return School::factory()->create();
    }

    private function academicYear(string $semester = 'ganjil'): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2026/2027',
            'semester' => $semester,
            'is_active' => false,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
    }

    private function decree(School $school, array $attrs = []): InstitutionDecree
    {
        return InstitutionDecree::create(array_merge([
            'id' => (string) Str::uuid(),
            'decree_number' => 'SK-'.Str::random(6),
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'Pembagian Tugas',
            'school_id' => $school->id,
            'academic_year_id' => $this->academicYear()->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-13',
            'end_date' => '2026-12-31',
            'status' => InstitutionDecree::STATUS_ACTIVE,
        ], $attrs));
    }

    public function test_semester_dari_tanggal(): void
    {
        $this->assertSame('ganjil', DecreePeriodService::semesterOfDate(Carbon::parse('2026-07-01')));
        $this->assertSame('ganjil', DecreePeriodService::semesterOfDate(Carbon::parse('2026-12-31')));
        $this->assertSame('genap', DecreePeriodService::semesterOfDate(Carbon::parse('2027-01-01')));
        $this->assertSame('genap', DecreePeriodService::semesterOfDate(Carbon::parse('2027-06-30')));
    }

    public function test_sk_berakhir_desember_berarti_ganjil(): void
    {
        $school = $this->school();
        $decree = $this->decree($school);

        $result = app(DecreePeriodService::class)->resolve($school->id, null, Carbon::parse('2026-09-01'));

        $this->assertSame('ganjil', $result['semester']);
        $this->assertSame('decree', $result['source']);
        $this->assertSame('active', $result['state']);
        $this->assertNull($result['message']);
        $this->assertSame($decree->id, $result['decree']->id);
    }

    public function test_sk_berakhir_juni_berarti_genap(): void
    {
        $school = $this->school();
        $this->decree($school, [
            'effective_date' => '2027-01-05',
            'end_date' => '2027-06-30',
        ]);

        $result = app(DecreePeriodService::class)->resolve($school->id, null, Carbon::parse('2027-03-01'));

        $this->assertSame('genap', $result['semester']);
        $this->assertSame('active', $result['state']);
    }

    public function test_judul_ganjil_tidak_menipu_ketika_masa_berlaku_berakhir_juni(): void
    {
        $school = $this->school();
        // Judul menyebut GANJIL, tetapi masa berlaku sampai Juni → genap.
        $this->decree($school, [
            'title' => 'PEMBAGIAN TUGAS MENGAJAR SEMESTER GANJIL',
            'effective_date' => '2026-10-30',
            'end_date' => '2027-06-30',
        ]);

        $result = app(DecreePeriodService::class)->resolve($school->id, null, Carbon::parse('2027-02-01'));

        $this->assertSame('genap', $result['semester']);
    }

    public function test_sk_kedaluwarsa_tetap_dipakai_dengan_peringatan(): void
    {
        $school = $this->school();
        $this->decree($school, [
            'effective_date' => '2026-07-13',
            'end_date' => '2026-10-30',
        ]);

        $result = app(DecreePeriodService::class)->resolve($school->id, null, Carbon::parse('2026-11-26'));

        $this->assertSame('decree', $result['source']);
        $this->assertSame('expired', $result['state']);
        $this->assertNotNull($result['message']);
        $this->assertStringContainsString('sudah berakhir', $result['message']);
    }

    public function test_tanpa_sk_jatuh_ke_tahun_ajaran_dengan_pemberitahuan(): void
    {
        $school = $this->school();
        $ay = $this->academicYear('genap');

        $result = app(DecreePeriodService::class)->resolve($school->id, $ay->id, Carbon::parse('2026-09-01'));

        $this->assertSame('academic_year', $result['source']);
        $this->assertSame('missing', $result['state']);
        $this->assertSame('genap', $result['semester']);
        $this->assertStringContainsString('belum punya SK', $result['message']);
    }

    public function test_sk_sekolah_lain_tidak_dipakai(): void
    {
        $a = $this->school();
        $b = $this->school();
        $this->decree($a);

        $result = app(DecreePeriodService::class)->resolve($b->id, null, Carbon::parse('2026-09-01'));

        $this->assertSame('missing', $result['state']);
    }

    public function test_masa_berlaku_inklusif_sampai_akhir_hari_berakhir(): void
    {
        $school = $this->school();
        $decree = $this->decree($school, [
            'effective_date' => '2026-07-13',
            'end_date' => '2026-10-30',
        ]);

        $service = app(DecreePeriodService::class);

        $this->assertTrue($service->covers($decree, Carbon::parse('2026-10-30 23:59:00')));
        $this->assertFalse($service->covers($decree, Carbon::parse('2026-10-31 00:01:00')));
    }

    public function test_tanpa_end_date_dianggap_tanpa_batas(): void
    {
        $school = $this->school();
        $decree = $this->decree($school, ['end_date' => null]);

        $service = app(DecreePeriodService::class);

        $this->assertTrue($service->covers($decree, Carbon::parse('2030-05-01')));
    }

    public function test_tanpa_end_date_judul_menentukan_semester(): void
    {
        $school = $this->school();
        // Data nyata: SK berlaku sejak Juli tanpa end_date, tetapi untuk genap.
        $decree = $this->decree($school, [
            'title' => 'Pembagian Tugas Semester Genap Tahun Pelajaran 2025/2026',
            'effective_date' => '2026-07-01',
            'end_date' => null,
        ]);

        $this->assertSame('genap', app(DecreePeriodService::class)->semesterFromDecree($decree));
    }

    public function test_end_date_mengalahkan_judul(): void
    {
        $school = $this->school();
        // Judul basi ("ganjil") tetapi masa berlaku jelas berakhir Desember.
        $decree = $this->decree($school, [
            'title' => 'Pembagian Tugas Semester Ganjil',
            'end_date' => '2026-12-31',
        ]);

        $this->assertSame('ganjil', app(DecreePeriodService::class)->semesterFromDecree($decree));
    }

    public function test_baca_semester_dari_judul(): void
    {
        $service = app(DecreePeriodService::class);

        $this->assertSame('genap', $service->semesterFromTitle('SK Semester Genap 2025/2026'));
        $this->assertSame('ganjil', $service->semesterFromTitle('SK Semester Ganjil 2026/2027'));
        $this->assertNull($service->semesterFromTitle('Pembagian Tugas Mengajar'));
    }
}
