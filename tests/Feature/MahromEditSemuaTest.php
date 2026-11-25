<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdditionalTaskType;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman edit mahrom menampilkan SELURUH mahrom santri, bukan hanya satu.
 */
class MahromEditSemuaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Student $student;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Edit Semua Mahrom', 'code' => 'UU-ESM',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99887766',
            'name' => 'Sekolah Edit Semua Mahrom',
            'boarding_type' => 'boarding',
        ]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '3333000001',
            'nis' => 'E-0001',
            'name' => 'Santri Banyak Mahrom',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
    }

    private function buatMahrom(string $nama, string $hubungan, bool $utama = false): StudentMahrom
    {
        return StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => $nama,
            'relationship' => $hubungan,
            'is_active' => true,
            'is_primary' => $utama,
        ]);
    }

    public function test_halaman_edit_menampilkan_seluruh_mahrom_santri(): void
    {
        $this->buatMahrom('Bapak Ahmad', 'ayah', true);
        $this->buatMahrom('Ibu Siti', 'ibu');
        $this->buatMahrom('Paman Yusuf', 'paman');
        $kakak = $this->buatMahrom('Kakak Zahra', 'kakak');

        $this->actingAs($this->admin)
            ->get(route('user.students.mahroms.edit', [
                'userId' => $this->admin->id,
                'santriUuid' => $this->student->id,
                'mahromUuid' => $kakak->id,
            ]))
            ->assertOk()
            ->assertSee('Bapak Ahmad')
            ->assertSee('Ibu Siti')
            ->assertSee('Paman Yusuf')
            ->assertSee('Kakak Zahra')
            ->assertSee('4 mahrom');
    }

    public function test_setiap_mahrom_punya_form_simpan_sendiri(): void
    {
        $this->buatMahrom('Bapak Form', 'ayah');
        $ibu = $this->buatMahrom('Ibu Form', 'ibu');

        $html = $this->actingAs($this->admin)
            ->get(route('user.students.mahroms.edit', [
                'userId' => $this->admin->id,
                'santriUuid' => $this->student->id,
                'mahromUuid' => $ibu->id,
            ]))
            ->assertOk()
            ->getContent();

        // Dua kartu = dua form edit independen.
        $this->assertSame(2, substr_count($html, 'class="form-edit-mahrom"'));
        $this->assertStringContainsString(route('user.students.mahroms.update', [
            'userId' => $this->admin->id,
            'santriUuid' => $this->student->id,
            'mahromUuid' => $ibu->id,
        ]), $html);
    }

    public function test_mahrom_yang_diklik_dibuka_otomatis(): void
    {
        $ayah = $this->buatMahrom('Ayah Fokus', 'ayah');
        $this->buatMahrom('Ibu Fokus', 'ibu');

        $html = $this->actingAs($this->admin)
            ->get(route('user.students.mahroms.edit', [
                'userId' => $this->admin->id,
                'santriUuid' => $this->student->id,
                'mahromUuid' => $ayah->id,
            ]))
            ->assertOk()
            ->getContent();

        // Kartu pertama adalah mahrom yang diklik, dan panelnya terbuka.
        $this->assertSame(0, strpos($html, 'Ayah Fokus') < strpos($html, 'Ibu Fokus') ? 0 : 1);
        $this->assertSame(1, substr_count($html, 'collapse show'));
    }

    public function test_simpan_kembali_ke_halaman_edit(): void
    {
        $ayah = $this->buatMahrom('Ayah Lama', 'ayah');

        $this->actingAs($this->admin)
            ->put(route('user.students.mahroms.update', [
                'userId' => $this->admin->id,
                'santriUuid' => $this->student->id,
                'mahromUuid' => $ayah->id,
            ]), [
                'name' => 'Ayah Baru',
                'relationship' => 'ayah',
                'phone' => '081234567890',
                'is_active' => 1,
            ])
            ->assertRedirect(route('user.students.mahroms.edit', [
                'userId' => $this->admin->id,
                'santriUuid' => $this->student->id,
                'mahromUuid' => $ayah->id,
            ]));

        $this->assertSame('Ayah Baru', $ayah->fresh()->name);
    }

    public function test_seeder_menyediakan_koordinator_perpustakaan(): void
    {
        $this->seed(\Database\Seeders\JenisGtkSeeder::class);
        $this->seed(\Database\Seeders\AdditionalTaskTypeSeeder::class);

        $tugas = AdditionalTaskType::where('kode', 'koordinator-perpustakaan')->first();

        $this->assertNotNull($tugas, 'Tugas "Koordinator Perpustakaan" tidak dibuat seeder.');
        $this->assertSame('Koordinator Perpustakaan', $tugas->nama);
        $this->assertTrue((bool) $tugas->is_active);

        // Harus terikat ke jenis_gtk Perpustakaan, bukan jenis lain.
        $jenis = $tugas->jenisGtk;
        $this->assertNotNull($jenis);
        $this->assertStringContainsString('Perpustakaan', (string) $jenis->nama);
    }

    public function test_seeder_idempoten_tidak_menduplikasi_tugas(): void
    {
        $this->seed(\Database\Seeders\JenisGtkSeeder::class);
        $this->seed(\Database\Seeders\AdditionalTaskTypeSeeder::class);
        $sebelum = AdditionalTaskType::count();

        $this->seed(\Database\Seeders\AdditionalTaskTypeSeeder::class);

        $this->assertSame($sebelum, AdditionalTaskType::count());
        $this->assertSame(1, AdditionalTaskType::where('kode', 'koordinator-perpustakaan')->count());
    }
}
