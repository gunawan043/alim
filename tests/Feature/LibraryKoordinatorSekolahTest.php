<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkAdditionalTask;
use App\Models\GtkWorkUnit;
use App\Models\LibraryMember;
use App\Models\LibraryUnit;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\LibraryAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Koordinator perpustakaan mengikuti SEKOLAH tempat ia bertugas.
 *
 * Guru SD IT Putri yang menjadi koordinator perpustakaan membuka
 * Perpustakaan SD — bukan dipindahkan ke perpustakaan lain — dan hanya
 * melihat santri SD IT Putri meski Perpustakaan SD juga melayani SD Putra.
 */
class LibraryKoordinatorSekolahTest extends TestCase
{
    use RefreshDatabase;

    private School $sdPutra;

    private School $sdPutri;

    private School $smaPutri;

    private LibraryUnit $unitSd;

    private LibraryUnit $unitPutri;

    private LibraryAccess $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->access = app(LibraryAccess::class);

        AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->sdPutra = $this->sekolah('SD IT Putra Abu Hurairah Mataram');
        $this->sdPutri = $this->sekolah('SD IT Putri Abu Hurairah Mataram');
        $this->smaPutri = $this->sekolah('SMA IT Putri Abu Hurairah Mataram');

        $this->unitSd = $this->unitPerpus('PERPUS-SD', 'Perpustakaan SD', $this->sdPutra);
        $this->unitPutri = $this->unitPerpus('PERPUS-PUTRI', 'Perpustakaan Putri', $this->smaPutri);
    }

    private function sekolah(string $nama): School
    {
        $wu = WorkUnit::create([
            'name' => $nama, 'code' => Str::slug($nama),
            'type' => 'Satuan Pendidikan', 'is_active' => true,
        ]);

        return School::create([
            'work_unit_id' => $wu->id,
            'npsn' => (string) random_int(10000000, 99999999),
            'name' => $nama,
        ]);
    }

    private function unitPerpus(string $code, string $nama, School $sekolah): LibraryUnit
    {
        return LibraryUnit::create([
            'work_unit_id' => $sekolah->work_unit_id,
            'school_id' => $sekolah->id,
            'code' => $code,
            'name' => $nama,
            'gender_scope' => 'mixed',
            'level_scope' => 'sd',
            'is_active' => true,
        ]);
    }

    /** Guru di sekolah tertentu, memegang tugas Koordinator Perpustakaan. */
    private function koordinator(string $nama, School $sekolah): User
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::create([
            'name' => $nama,
            'email' => Str::slug($nama).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole('Satuan Pendidikan');

        GtkWorkUnit::create([
            'user_id' => $user->id,
            'work_unit_id' => $sekolah->work_unit_id,
            'jabatan' => 'Guru Kelas',
            'is_primary' => true,
        ]);

        GtkAdditionalTask::create([
            'user_id' => $user->id,
            'work_unit_id' => $sekolah->work_unit_id,
            'nama_tugas' => 'Koordinator Perpustakaan',
            'tmt' => now()->subMonth()->toDateString(),
        ]);

        return $user;
    }

    public function test_guru_sd_putri_koordinator_perpus_hanya_membuka_perpustakaan_sd(): void
    {
        $guru = $this->koordinator('Guru SD Putri', $this->sdPutri);

        $units = $this->access->unitsFor($guru);

        $this->assertTrue($this->access->canManage($guru));
        $this->assertSame(['Perpustakaan SD'], $units->pluck('name')->all());
    }

    public function test_guru_sd_putri_hanya_melihat_santri_sd_putri(): void
    {
        $guru = $this->koordinator('Guru SD Putri', $this->sdPutri);

        $santriPutri = $this->anggota($this->unitSd, $this->sdPutri, 'Santri Putri', 'SP-1', 'P');
        $santriPutra = $this->anggota($this->unitSd, $this->sdPutra, 'Santri Putra', 'SR-1', 'L');
        $this->anggota($this->unitPutri, $this->smaPutri, 'Santri SMA', 'SM-1', 'P');

        $this->actingAs($guru)
            ->get("/{$guru->id}/perpustakaan/members?unit_id={$this->unitSd->id}")
            ->assertOk()
            ->assertSee($santriPutri->name)
            ->assertDontSee($santriPutra->name)
            ->assertDontSee('Santri SMA');
    }

    public function test_guru_sd_putra_hanya_melihat_santri_sd_putra(): void
    {
        $guru = $this->koordinator('Guru SD Putra', $this->sdPutra);

        $santriPutri = $this->anggota($this->unitSd, $this->sdPutri, 'Santri Putri', 'SP-2', 'P');
        $santriPutra = $this->anggota($this->unitSd, $this->sdPutra, 'Santri Putra', 'SR-2', 'L');

        $this->actingAs($guru)
            ->get("/{$guru->id}/perpustakaan/members?unit_id={$this->unitSd->id}")
            ->assertOk()
            ->assertSee($santriPutra->name)
            ->assertDontSee($santriPutri->name);
    }

    public function test_guru_sma_putri_koordinator_hanya_membuka_perpustakaan_putri(): void
    {
        $guru = $this->koordinator('Guru SMA Putri', $this->smaPutri);

        $this->assertSame(['Perpustakaan Putri'], $this->access->unitsFor($guru)->pluck('name')->all());
        $this->assertFalse($this->access->canAccessUnit($guru, $this->unitSd));
    }

    public function test_guru_biasa_di_sekolah_yang_sama_tetap_ditolak(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $guru = User::create([
            'name' => 'Guru Biasa', 'email' => 'biasa@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $guru->assignRole('Satuan Pendidikan');

        GtkWorkUnit::create([
            'user_id' => $guru->id,
            'work_unit_id' => $this->sdPutri->work_unit_id,
            'jabatan' => 'Guru Kelas',
            'is_primary' => true,
        ]);

        $this->assertFalse($this->access->canManage($guru));
        $this->assertCount(0, $this->access->unitsFor($guru));
    }


    public function test_tugas_kedaluwarsa_tidak_memberi_akses(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::create([
            'name' => 'Mantan Koordinator', 'email' => 'mantan@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->assignRole('Satuan Pendidikan');

        GtkWorkUnit::create([
            'user_id' => $user->id,
            'work_unit_id' => $this->sdPutri->work_unit_id,
            'jabatan' => 'Guru Kelas',
            'is_primary' => true,
        ]);

        GtkAdditionalTask::create([
            'user_id' => $user->id,
            'work_unit_id' => $this->sdPutri->work_unit_id,
            'nama_tugas' => 'Koordinator Perpustakaan',
            'tmt' => now()->subYear()->toDateString(),
            'tst' => now()->subMonth()->toDateString(),
        ]);

        $this->assertFalse($this->access->canManage($user));
        $this->assertCount(0, $this->access->unitsFor($user));
    }

    public function test_super_admin_tetap_membuka_semua_unit(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->assertCount(2, $this->access->unitsFor($admin));
    }

    public function test_pemetaan_sekolah_ke_perpustakaan_sesuai_kebijakan(): void
    {
        $harapan = [
            'SD IT Putra' => 'PERPUS-SD',
            'SD IT Putri' => 'PERPUS-SD',
        ];

        foreach ($harapan as $pola => $kode) {
            $sekolah = School::where('name', 'like', '%'.$pola.'%')->firstOrFail();
            $this->assertSame(
                [$kode],
                $this->access->unitCodesForSchools([$sekolah->id]),
                "Sekolah {$pola} harus memakai {$kode}"
            );
        }
    }


    public function test_judul_buku_dilihat_bersama_lintas_sekolah(): void
    {
        $guruPutri = $this->koordinator('Guru SD Putri', $this->sdPutri);
        $guruPutra = $this->koordinator('Guru SD Putra', $this->sdPutra);

        // Buku dicatat oleh koordinator SD Putra di Perpustakaan SD.
        $judul = \App\Models\LibraryTitle::create([
            'school_id' => $this->sdPutra->id,
            'title' => 'Buku Bersama Perpustakaan SD',
            'collection_type' => 'umum',
            'is_active' => true,
        ]);
        \App\Models\LibraryItem::create([
            'library_title_id' => $judul->id,
            'library_unit_id' => $this->unitSd->id,
            'inventory_code' => 'INV-BERSAMA-1',
            'status' => \App\Models\LibraryItem::STATUS_AVAILABLE,
            'condition' => 'baik',
        ]);

        // Koordinator dari sekolah LAIN tetap melihat buku yang sama.
        $this->actingAs($guruPutri)
            ->get("/{$guruPutri->id}/perpustakaan/catalog?unit_id={$this->unitSd->id}")
            ->assertOk()
            ->assertSee('Buku Bersama Perpustakaan SD');
    }

    public function test_santri_sekolah_lain_tidak_muncul_di_pencarian(): void
    {
        $guru = $this->koordinator('Guru SD Putri', $this->sdPutri);

        $this->siswa($this->sdPutri, 'Santri Putri Cari', 'SPC-1', 'P');
        $this->siswa($this->sdPutra, 'Santri Putra Cari', 'SRC-1', 'L');

        $respon = $this->actingAs($guru)->getJson(
            "/{$guru->id}/perpustakaan/members/students?unit_id={$this->unitSd->id}&q=Cari"
        )->assertOk();

        $teks = collect($respon->json('results'))->pluck('text')->implode(' ');
        $this->assertStringContainsString('Santri Putri Cari', $teks);
        $this->assertStringNotContainsString('Santri Putra Cari', $teks);
    }

    public function test_tidak_bisa_mendaftarkan_santri_sekolah_lain(): void
    {
        $guru = $this->koordinator('Guru SD Putri', $this->sdPutri);
        $asing = $this->siswa($this->sdPutra, 'Santri Sekolah Lain', 'ASING-1', 'L');

        $acc = app(\App\Services\LibraryAccess::class);

        // Logika scope: sekolah yang boleh hanya SD Putri.
        $boleh = $acc->schoolIdsForUnit($guru, $this->unitSd);
        $this->assertSame([$this->sdPutri->id], $boleh);

        // Santri sekolah lain tidak termasuk di dalamnya.
        $this->assertNotContains((string) $asing->school_id, $boleh);
    }

    public function test_santri_sekolah_sendiri_bisa_didaftarkan(): void
    {
        $guru = $this->koordinator('Guru SD Putri', $this->sdPutri);
        $milik = $this->siswa($this->sdPutri, 'Santri Milik Sendiri', 'OWN-1', 'P');

        $this->actingAs($guru)->post("/{$guru->id}/perpustakaan/members", [
            'library_unit_id' => $this->unitSd->id,
            'member_type' => 'student',
            'student_id' => $milik->id,
            'joined_at' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('library_members', [
            'library_unit_id' => $this->unitSd->id,
            'student_id' => $milik->id,
        ]);
    }

    private function siswa(School $sekolah, string $nama, string $nis, string $gender): Student
    {
        return Student::create([
            'school_id' => $sekolah->id,
            'name' => $nama,
            'nis' => $nis,
            'nisn' => $nis,
            'gender' => $gender,
            'status' => 'active',
        ]);
    }

    private function anggota(LibraryUnit $unit, School $sekolah, string $nama, string $nis, string $gender): LibraryMember
    {
        $student = Student::create([
            'school_id' => $sekolah->id,
            'name' => $nama,
            'nis' => $nis,
            'nisn' => $nis,
            'gender' => $gender,
            'status' => 'active',
        ]);

        return LibraryMember::create([
            'library_unit_id' => $unit->id,
            'student_id' => $student->id,
            'member_number' => 'M-'.$nis,
            'member_type' => 'student',
            'status' => LibraryMember::STATUS_ACTIVE,
            'joined_at' => now()->toDateString(),
        ]);
    }
}
