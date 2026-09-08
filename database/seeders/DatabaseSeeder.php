<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Hanya berisi seeders struktural/ketenagakerjaan untuk production.
     * Data dummy operasional (siswa, kelas, rombel, sarpras, dll) tidak disertakan.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            SystemSuperAdminSeeder::class,  // permanent system admin — runs AFTER roles/permissions
            PermissionRoleSeeder::class,   // assign permissions to roles

            WilayahSeeder::class,          // provinces/cities/districts/villages (FK target for GTK)
            DivisiSeeder::class,           // master divisi — harus duluan sebelum WorkUnitSeeder
            WorkUnitSeeder::class,         // satuan kerja (linked to divisi) — harus duluan sebelum SchoolSeeder
            JenisGtkSeeder::class,         // master jenis GTK & jabatan
            AdditionalTaskTypeSeeder::class, // master tugas tambahan (FK ke jenis_gtk)
            UksWorkUnitSeeder::class,      // UKS Putra & UKS Putri satker units
            PermitTypeSeeder::class,       // master jenis izin (pulang, sakit, dll.)
            SchoolSeeder::class,       // master jenis izin (pulang, sakit, dll.)
            DormitorySeeder::class,       // master jenis izin (pulang, sakit, dll.)
            AcademicYearSeeder::class,       // master jenis izin (pulang, sakit, dll.)
            DokumenIsoSeeder::class,       // master jenis izin (pulang, sakit, dll.)
        ]);
    }
}
