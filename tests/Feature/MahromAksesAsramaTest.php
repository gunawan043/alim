<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Akses edit mahrom untuk user asrama.
 *
 * Sebelumnya cakupan mahrom diambil dari penugasan asrama per user. Hampir
 * semua user asrama belum punya penugasan, sehingga halaman Data Mahrom
 * kosong dan tidak ada yang bisa diedit. Sekarang cakupannya mengikuti menu
 * Data Santri: seluruh santri yang mondok.
 */
class MahromAksesAsramaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormitory;

    private AcademicYear $tahunAjaran;

    private Student $santriMondok;

    private User $userAsrama;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Mahrom Asrama', 'code' => 'UU-MA',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99887799',
            'name' => 'Sekolah Mahrom Asrama',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'D-MA-1',
            'name' => 'Asrama Mahrom Uji',
            'gender' => 'putra',
            'is_active' => true,
        ]);

        $this->tahunAjaran = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->santriMondok = $this->buatSantri('Santri Mondok Uji', '4444000001');

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->santriMondok->id,
            'dormitory_id' => $this->dormitory->id,
            'academic_year_id' => $this->tahunAjaran->id,
            'is_active' => true,
            'check_in_date' => now()->toDateString(),
        ]);

        // User asrama TANPA penugasan asrama — kondisi mayoritas di produksi.
        $this->userAsrama = User::factory()->create(['is_active' => true]);
        $this->userAsrama->assignRole('Asrama');
    }

    private function buatSantri(string $nama, string $nisn): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => $nisn,
            'nis' => 'N-'.$nisn,
            'name' => $nama,
            'gender' => 'L',
            'status' => 'active',
        ]);
    }

    private function buatMahrom(Student $santri, string $nama, string $hubungan): StudentMahrom
    {
        return StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $santri->id,
            'name' => $nama,
            'relationship' => $hubungan,
            'is_active' => true,
        ]);
    }

    public function test_user_asrama_tanpa_penugasan_tetap_melihat_mahrom_santri_mondok(): void
    {
        $this->buatMahrom($this->santriMondok, 'Bapak Mondok', 'ayah');

        // Prasyarat: memang tidak punya penugasan asrama.
        $this->assertSame([], $this->userAsrama->accessibleDormitoryIds());

        $this->actingAs($this->userAsrama)
            ->get(route('user.students.mahroms.global', ['userId' => $this->userAsrama->id]))
            ->assertOk()
            ->assertSee('Bapak Mondok');
    }

    public function test_user_asrama_bisa_membuka_dan_menyimpan_edit_mahrom(): void
    {
        $mahrom = $this->buatMahrom($this->santriMondok, 'Bapak Edit Asrama', 'ayah');

        $this->actingAs($this->userAsrama)
            ->get(route('user.students.mahroms.globalEdit', [
                'userId' => $this->userAsrama->id,
                'mahromUuid' => $mahrom->id,
            ]))
            ->assertOk()
            ->assertSee('Bapak Edit Asrama');

        $this->actingAs($this->userAsrama)
            ->put(route('user.students.mahroms.globalUpdate', [
                'userId' => $this->userAsrama->id,
                'mahromUuid' => $mahrom->id,
            ]), [
                'student_id' => $this->santriMondok->id,
                'name' => 'Bapak Edit Asrama Baru',
                'relationship' => 'ayah',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('Bapak Edit Asrama Baru', $mahrom->fresh()->name);
    }

    public function test_user_asrama_tidak_melihat_mahrom_santri_tidak_mondok(): void
    {
        $santriNonMondok = $this->buatSantri('Santri Pulang', '4444000002');
        $this->buatMahrom($santriNonMondok, 'Bapak Non Mondok', 'ayah');
        $mahrom = $this->buatMahrom($santriNonMondok, 'Paman Non Mondok', 'paman');

        $this->actingAs($this->userAsrama)
            ->get(route('user.students.mahroms.global', ['userId' => $this->userAsrama->id]))
            ->assertOk()
            ->assertDontSee('Bapak Non Mondok');

        // Membuka langsung pun harus ditolak.
        $this->actingAs($this->userAsrama)
            ->get(route('user.students.mahroms.globalEdit', [
                'userId' => $this->userAsrama->id,
                'mahromUuid' => $mahrom->id,
            ]))
            ->assertNotFound();
    }

    public function test_non_asrama_tidak_terpengaruh_aturan_asrama(): void
    {
        $guru = User::factory()->create(['is_active' => true]);
        $guru->assignRole('Satuan Pendidikan');

        $this->assertNull($guru->mahromDormitoryScope());
    }
}
