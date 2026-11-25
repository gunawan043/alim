<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman edit mahrom (versi global) menampilkan SELURUH mahrom santri,
 * bukan hanya catatan yang diklik. Saat santri diganti, daftar mahrom harus
 * mengikuti santri yang baru dipilih.
 */
class MahromEditGlobalSemuaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Student $santriA;

    private Student $santriB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $wu = WorkUnit::create([
            'name' => 'SD IT Putra', 'code' => 'SD-IT-PUTRA',
            'type' => 'Satuan Pendidikan', 'is_active' => true,
        ]);

        $this->school = School::create([
            'work_unit_id' => $wu->id,
            'npsn' => '12345678',
            'name' => 'SD IT Putra Abu Hurairah Mataram',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin.sekolah@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->santriA = $this->santri('Ahmad Santri A', 'A-001');
        $this->santriB = $this->santri('Bilal Santri B', 'B-001');
    }

    private function santri(string $nama, string $nis): Student
    {
        return Student::create([
            'school_id' => $this->school->id,
            'name' => $nama,
            'nis' => $nis,
            'nisn' => $nis,
            'gender' => 'L',
            'status' => 'active',
        ]);
    }

    private function mahrom(Student $santri, string $nama, string $relasi): StudentMahrom
    {
        return StudentMahrom::create([
            'student_id' => $santri->id,
            'name' => $nama,
            'relationship' => $relasi,
            'is_active' => true,
            'is_primary' => false,
        ]);
    }

    public function test_halaman_edit_menampilkan_seluruh_mahrom_santri(): void
    {
        $a1 = $this->mahrom($this->santriA, 'Ayah Ahmad', 'ayah');
        $this->mahrom($this->santriA, 'Ibu Ahmad', 'ibu');
        $this->mahrom($this->santriA, 'Kakak Ahmad', 'kakak');

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/students/mahroms/{$a1->id}/edit")
            ->assertOk()
            ->assertSee('Ayah Ahmad')
            ->assertSee('Ibu Ahmad')
            ->assertSee('Kakak Ahmad')
            ->assertSee('Seluruh Mahrom Santri Ini');
    }

    public function test_mahrom_santri_lain_tidak_ikut_tampil(): void
    {
        $a1 = $this->mahrom($this->santriA, 'Ayah Ahmad', 'ayah');
        $this->mahrom($this->santriB, 'Ayah Bilal', 'ayah');

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/students/mahroms/{$a1->id}/edit")
            ->assertOk()
            ->assertSee('Ayah Ahmad')
            ->assertDontSee('Ayah Bilal');
    }

    public function test_simpan_memindahkan_mahrom_ke_santri_lain(): void
    {
        $a1 = $this->mahrom($this->santriA, 'Ayah Ahmad', 'ayah');

        $this->actingAs($this->admin)
            ->put("/{$this->admin->id}/students/mahroms/{$a1->id}", [
                'student_id' => $this->santriB->id,
                'name' => 'Ayah Ahmad',
                'relationship' => 'ayah',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_mahroms', [
            'id' => $a1->id,
            'student_id' => $this->santriB->id,
        ]);
    }

    public function test_endpoint_daftar_mahrom_per_santri(): void
    {
        $this->mahrom($this->santriB, 'Ibu Bilal', 'ibu');

        $this->actingAs($this->admin)
            ->getJson("/{$this->admin->id}/students/{$this->santriB->id}/mahrom/list")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Ibu Bilal']);
    }
}
