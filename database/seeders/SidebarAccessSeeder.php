<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SidebarAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SidebarAccessSeeder extends Seeder
{
    /**
     * Define the default sidebar access configuration.
     *
     * Key => display_name mapping. The allowed_roles will be
     * seeded from the existing PermissionRoleSeeder data.
     */
    private const MENU_DEFINITIONS = [
        'super-admin' => 'Super Admin',
        'waka' => 'Wakil Kepala Sekolah / Pimpinan',
        'admin-tu' => 'Admin Tata Usaha',
        'gtk' => 'GTK / Satuan Pendidikan',
        'unified-gtk' => 'GTK Unified',
        'satuan-pendidikan' => 'Satuan Pendidikan',
        'wali-kelas' => 'Wali Kelas',
        'coordinator-rumpun' => 'Koordinator Rumpun',
        'waka-kurikulum' => 'Waka Kurikulum',
        'head-asrama' => 'Kepala Asrama',
        'asrama' => 'Asrama (RO)',
        'admin-asrama' => 'Admin Asrama',
        'pendidikan-asrama' => 'Pendidikan Asrama',
        'kesehatan-asrama' => 'Kesehatan Asrama',
        'wali-asrama' => 'Wali Asrama',
        'uks' => 'UKS',
        'kepala-uks' => 'Kepala UKS',
        'admin-uks' => 'Admin UKS',
        'admin-uks-putra' => 'Admin UKS Putra',
        'admin-uks-putri' => 'Admin UKS Putri',
        'admin-kesehatan' => 'Admin Kesehatan',
        'sarpras' => 'Sarpras',
        'admin-sarpras' => 'Admin Sarpras',
        'personalia' => 'Personalia',
        'ats' => 'ATS',
        'humas-personalia' => 'Humas Personalia',
        'unit-rumah-tangga' => 'Unit Rumah Tangga',
        'teknologi-informasi' => 'Teknologi Informasi',
        'perpustakaan' => 'Perpustakaan',
        'unit-pelayanan-gizi' => 'Unit Pelayanan Gizi',
        'pimpinan' => 'Pimpinan',
        'satpam' => 'Satuan Keamanan',
        'departemen-tahfidz' => 'Departemen Tahfidz',
        'departemen-bahasa' => 'Departemen Bahasa',
        'wali-santri' => 'Wali Santri',
        'administrator' => 'Administrator',
        'keuangan' => 'Keuangan',
        'mudir' => 'Mudir',
    ];

    /**
     * Map each menu_key to the actual existing DB role names that should
     * be able to access it. Populated from PermissionRoleSeeder semantics
     * but using only roles that actually exist in the database.
     */
    private const ROLE_ASSIGNMENTS = [
        'super-admin' => ['Super Admin'],
        // Mudir / Pimpinan — use "Pimpinan" role since "Mudir" & "Kepala Sekolah" don't exist
        'waka' => ['Pimpinan'],
        // Admin Tata Usaha — "Admin Tata Usaha" role doesn't exist; use "Administrator"
        'admin-tu' => ['Administrator'],
        // GTK / Satuan Pendidikan — these are the actual GTK roles
        'gtk' => ['Satuan Pendidikan'],
        'unified-gtk' => ['Satuan Pendidikan'],
        // Wali Kelas / Koordinator Rumpun / Waka Kurikulum — exist under Satuan Pendidikan
        'wali-kelas' => ['Satuan Pendidikan'],
        'coordinator-rumpun' => ['Satuan Pendidikan'],
        'waka-kurikulum' => ['Satuan Pendidikan'],
        // Asrama modules — map from PermissionRoleSeeder where possible
        'head-asrama' => ['Asrama'],    // no Kepala Asrama role; Asrama handles it
        'asrama' => ['Asrama'],
        'admin-asrama' => [],    // "Admin Asrama" role doesn't exist
        'pendidikan-asrama' => ['Asrama'],    // Admin Pendidikan was deleted; Asrama handles it
        'kesehatan-asrama' => [],    // "Admin Kesehatan" role doesn't exist
        'wali-asrama' => [],    // "Wali Asrama" role doesn't exist
        // UKS — Kepala UKS & UKS exist; Admin UKS Putra/Putri don't
        'uks' => ['UKS'],
        'kepala-uks' => [],    // Kepala UKS role doesn't exist; allow-all (fallback)
        'admin-uks' => [],    // "Admin UKS" role doesn't exist
        'admin-uks-putra' => ['UKS'],    // no dedicated putra role; UKS handles it
        'admin-uks-putri' => ['UKS'],    // no dedicated putri role; UKS handles it
        'admin-kesehatan' => [],    // "Admin Kesehatan" role doesn't exist
        // Sarpras — roles don't exist in DB
        'sarpras' => [],
        'admin-sarpras' => [],
        // Personalia / Keuangan
        'personalia' => ['Humas Personalia'],
        'keuangan' => ['Keuangan'],
        // ATS — ATS role was deleted; Teknologi Informasi covers it
        'ats' => ['Teknologi Informasi'],
        // Humas Personalia
        'humas-personalia' => ['Humas Personalia'],
        // Unit Rumah Tangga
        'unit-rumah-tangga' => ['Unit Rumah Tangga'],
        // Teknologi Informasi
        'teknologi-informasi' => ['Teknologi Informasi'],
        // Perpustakaan
        'perpustakaan' => ['Perpustakaan'],
        // Unit Pelayanan Gizi
        'unit-pelayanan-gizi' => ['Unit Pelayanan Gizi'],
        // Pimpinan (Mudir view)
        'pimpinan' => ['Pimpinan'],
        // Satuan Keamanan (SATPAM)
        'satpam' => ['Satuan Keamanan'],
        // Departemen Tahfidz
        'departemen-tahfidz' => ['Departemen Tahfidz'],
        // Departemen Bahasa
        'departemen-bahasa' => ['Departemen Bahasa'],
        // Wali Santri — no dedicated role
        'wali-santri' => [],
        // Administrator (same as admin-tu)
        'administrator' => ['Administrator'],
        // Mudir — same as waka
        'mudir' => ['Pimpinan'],
        // Satuan Pendidikan (separate entry)
        'satuan-pendidikan' => ['Satuan Pendidikan'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update sidebar accesses from definitions
        foreach (self::MENU_DEFINITIONS as $menuKey => $displayName) {
            $roles = self::ROLE_ASSIGNMENTS[$menuKey] ?? [];
            SidebarAccess::updateOrCreate(
                ['menu_key' => $menuKey],
                ['display_name' => $displayName, 'allowed_roles' => $roles]
            );
        }

        // Delete entries for menu keys that no longer exist
        SidebarAccess::whereNotIn('menu_key', array_keys(self::MENU_DEFINITIONS))->delete();
    }
}
