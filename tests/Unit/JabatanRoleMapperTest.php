<?php

namespace Tests\Unit;

use App\Helpers\JabatanRoleMapper;
use PHPUnit\Framework\TestCase;

class JabatanRoleMapperTest extends TestCase
{
    /** @dataProvider jabatanToRoleProvider */
    public function test_maps_jabatan_to_correct_role(string $jabatan, string $expected): void
    {
        $this->assertEquals($expected, JabatanRoleMapper::resolve($jabatan));
    }

    /** @dataProvider invalidJabatanProvider */
    public function test_falls_back_to_satuan_pendidikan_for_unknown_jabatan(string $jabatan): void
    {
        $this->assertEquals('Satuan Pendidikan', JabatanRoleMapper::resolve($jabatan));
    }

    public static function jabatanToRoleProvider(): array
    {
        return [
            // Super Admin
            'System Administrator' => ['System Administrator', 'Super Admin'],
            '  system  administrator  ' => ['  system  administrator  ', 'Super Admin'],

            // Pimpinan
            'Mudir' => ['Mudir', 'Pimpinan'],
            'Wadir 1' => ['Wadir 1', 'Pimpinan'],
            'Wadir 2' => ['Wadir 2', 'Pimpinan'],

            // Satuan Pendidikan
            'Kepala Satuan Pendidikan' => ['Kepala Satuan Pendidikan', 'Satuan Pendidikan'],
            'Wakil Kepala Satuan Pendidikan' => ['Wakil Kepala Satuan Pendidikan', 'Satuan Pendidikan'],
            'Guru Umum' => ['Guru Umum', 'Satuan Pendidikan'],
            'Guru Kelas' => ['Guru Kelas', 'Satuan Pendidikan'],
            'Guru Agama' => ['Guru Agama', 'Satuan Pendidikan'],
            'Guru Hadits' => ['Guru Hadits', 'Satuan Pendidikan'],
            'Guru Bahasa Arab' => ['Guru Bahasa Arab', 'Satuan Pendidikan'],
            'Guru Tahfidz' => ['Guru Tahfidz', 'Satuan Pendidikan'],

            // Asrama
            'Kepala Asrama' => ['Kepala Asrama', 'Asrama'],
            'Wakil Kepala Asrama' => ['Wakil Kepala Asrama', 'Asrama'],
            'Musyrif' => ['Musyrif', 'Asrama'],
            'Musyrifah' => ['Musyrifah', 'Asrama'],
            'Tata Usaha Asrama' => ['Tata Usaha Asrama', 'Asrama'],
            'Staf Perizinan' => ['Staf Perizinan', 'Asrama'],
            'Perizinan' => ['Perizinan', 'Asrama'],

            // UKS
            'Kepala UKS' => ['Kepala UKS', 'UKS'],
            'Staf UKS Putra' => ['Staf UKS Putra', 'UKS'],
            'Staf UKS Putri' => ['Staf UKS Putri', 'UKS'],
            'Admin UKS Putra' => ['Admin UKS Putra', 'UKS'],

            // Departemen Tahfidz
            'Kepala Departemen Tahfidz' => ['Kepala Departemen Tahfidz', 'Departemen Tahfidz'],
            'Wakil Kepala Departemen Tahfidz' => ['Wakil Kepala Departemen Tahfidz', 'Departemen Tahfidz'],
            'Tata Usaha Departemen Tahfidz' => ['Tata Usaha Departemen Tahfidz', 'Departemen Tahfidz'],

            // Departemen Bahasa
            'Kepala Departemen Bahasa' => ['Kepala Departemen Bahasa', 'Departemen Bahasa'],
            'Wakil Kepala Departemen Bahasa' => ['Wakil Kepala Departemen Bahasa', 'Departemen Bahasa'],
            'Tata Usaha Departemen Bahasa' => ['Tata Usaha Departemen Bahasa', 'Departemen Bahasa'],

            // Perpustakaan
            'Koordinator Perpustakaan' => ['Koordinator Perpustakaan', 'Perpustakaan'],
            'Staf Perpustakaan' => ['Staf Perpustakaan', 'Perpustakaan'],

            // Satuan Keamanan
            'Kepala Satuan Keamanan' => ['Kepala Satuan Keamanan', 'Satuan Keamanan'],
            'Koordinator Satuan Keamanan' => ['Koordinator Satuan Keamanan', 'Satuan Keamanan'],
            'Anggota Satuan Keamanan' => ['Anggota Satuan Keamanan', 'Satuan Keamanan'],

            // Humas Personalia
            'Kepala Humas & Personalia' => ['Kepala Humas & Personalia', 'Humas Personalia'],
            'Kepala Humas' => ['Kepala Humas', 'Humas Personalia'],
            'Kepala Personalia' => ['Kepala Personalia', 'Humas Personalia'],
            'Staf Humas' => ['Staf Humas', 'Humas Personalia'],
            'Staf Personalia' => ['Staf Personalia', 'Humas Personalia'],

            // Unit Rumah Tangga
            'Kepala Unit Rumah Tangga' => ['Kepala Unit Rumah Tangga', 'Unit Rumah Tangga'],
            'Koordinator Sarpras' => ['Koordinator Sarpras', 'Unit Rumah Tangga'],
            'Tata Usaha URT' => ['Tata Usaha URT', 'Unit Rumah Tangga'],
            'Staf URT' => ['Staf URT', 'Unit Rumah Tangga'],
            'Koordinator Kebersihan' => ['Koordinator Kebersihan', 'Unit Rumah Tangga'],
            'Staf Kebersihan' => ['Staf Kebersihan', 'Unit Rumah Tangga'],
            'Kepala Unit Gizi & Logistik' => ['Kepala Unit Gizi & Logistik', 'Unit Pelayanan Gizi'],
            // Coordinator Logistics & Gizi are under URT per JenisGtkSeeder (role: Unit Rumah Tangga)
            'Koordinator Logistik' => ['Koordinator Logistik', 'Unit Pelayanan Gizi'],
            'Staf Gizi' => ['Staf Gizi', 'Unit Pelayanan Gizi'],
            'Staf Logistik' => ['Staf Logistik', 'Unit Pelayanan Gizi'],

            // Keuangan
            'Kepala Keuangan' => ['Kepala Keuangan', 'Keuangan'],
            'Staf Keuangan' => ['Staf Keuangan', 'Keuangan'],
            'Bendahara' => ['Bendahara', 'Keuangan'],

            // Teknologi Informasi
            'Kepala Unit Teknologi Informasi' => ['Kepala Unit Teknologi Informasi', 'Teknologi Informasi'],
            'Staf Teknologi Informasi' => ['Staf Teknologi Informasi', 'Teknologi Informasi'],
            'Teknisi Jaringan' => ['Teknisi Jaringan', 'Teknologi Informasi'],
        ];
    }

    public static function invalidJabatanProvider(): array
    {
        return [
            'empty string' => [''],
            'null-like' => ['Tidak Diketahui'],
            'random string' => ['Random Jabatan'],
            'single char' => ['X'],
        ];
    }

    public function test_validate_accepts_official_roles(): void
    {
        $official = [
            'Super Admin', 'Pimpinan', 'Satuan Pendidikan', 'Asrama', 'UKS',
            'Departemen Tahfidz', 'Departemen Bahasa', 'Perpustakaan',
            'Satuan Keamanan', 'Humas Personalia', 'Unit Rumah Tangga',
            'Keuangan', 'Teknologi Informasi', 'Unit Pelayanan Gizi',
        ];

        foreach ($official as $role) {
            $this->assertTrue(JabatanRoleMapper::validate($role), "Expected '$role' to be valid");
        }
    }

    public function test_validate_rejects_non_official_roles(): void
    {
        $invalid = ['gtk', 'Admin', 'Wali Kelas', 'Kepala Sekolah', 'Staff IT', ''];

        foreach ($invalid as $role) {
            $this->assertFalse(JabatanRoleMapper::validate($role), "Expected '$role' to be invalid");
        }
    }

    public function test_normalize_collapses_whitespace(): void
    {
        $this->assertEquals(
            'kepala satuan pendidikan',
            JabatanRoleMapper::normalize('  KEPALA   SATUAN   PENDIDIKAN  ')
        );
    }
}
