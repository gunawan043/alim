<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            SystemSuperAdminSeeder::class,  // permanent system admin — runs AFTER roles/permissions so it can be assigned the Super Admin role
            UksRolesSeeder::class,         // UKS roles must exist before PermissionRoleSeeder assigns permissions
            PermissionRoleSeeder::class,   // assign permissions to roles
            UserSeeder::class,
            JenisGtkSeeder::class,
            WilayahSeeder::class,         // provinces/cities/districts/villages FK target
            SchoolSeeder::class,
            AcademicYearSeeder::class,
            GradeLevelSeeder::class,
            StudyGroupSeeder::class,
            AssetCategorySeeder::class,    // kategori aset untuk import sarpras
            SarprasSeeder::class,          // sync Ruang Kelas → asset_rooms
            GtkDummySeeder::class,
            TodoSeeder::class,              // sample todo lists and tasks
            DormitorySeeder::class,         // sample dormitories, wings, rooms, users
            DormitoryDataSeeder::class,     // penghuni, absensi, izin, pelanggaran, mutasi, inventaris
            DormitoryPostSeeder::class,     // informasi, kunjungan, template kegiatan, broadcast
            PermitTypeSeeder::class,        // master jenis izin (pulang, sakit, dll.)
            DivisiSeeder::class,            // master divisi untuk dokumen ISO
            DokumenIsoSeeder::class,        // data dokumen ISO (~300 dokumen)
            UksWorkUnitSeeder::class,       // UKS Putra & UKS Putri satker units
            UksRolePermissionSeeder::class, // UKS Spatie roles + permissions
        ]);
    }
}
