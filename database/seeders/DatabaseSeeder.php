<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            PermissionRoleSeeder::class,  // assign permissions to roles
            UserSeeder::class,
            SidebarMenuSeeder::class,
            JenisGtkSeeder::class,
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
            DivisiSeeder::class,            // master divisi untuk dokumen ISO
            DokumenIsoSeeder::class,        // data dokumen ISO (~300 dokumen)
        ]);
    }
}