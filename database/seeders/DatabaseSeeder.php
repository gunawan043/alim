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

            // Domain layer (added Sep 2026) — must run after RoleSeeder + PermissionRoleSeeder
            DomainSeeder::class,                        // 14 official domains
            DomainPermissionSeeder::class,              // mirror role permissions into domain matrix
            SuperAdminDomainPermissionSeeder::class,   // super admin gets all permissions

            WilayahSeeder::class,          // provinces/cities/districts/villages (FK target for GTK)
            DivisiSeeder::class,           // master divisi — harus duluan sebelum WorkUnitSeeder
            WorkUnitSeeder::class,         // satuan kerja (linked to divisi) — harus duluan sebelum SchoolSeeder
            JenisGtkSeeder::class,         // master jenis GTK & jabatan
            AdditionalTaskTypeSeeder::class, // master tugas tambahan (FK ke jenis_gtk)
            AssetCategorySeeder::class,        // kategori aset dasar
            SarprasInventoryCategorySeeder::class, // golongan Buku Induk A-I
            SarprasChecklistTemplateSeeder::class, // checklist pemeliharaan gedung/AC/genset/dll.
            SarprasBuildingLocationSeeder::class, // master gedung dari formulir pemeliharaan (lokasi jelas)
            SarprasCleaningSeeder::class, // area, uraian, dan program kebersihan awal
            SarprasElectricitySeeder::class, // meter listrik & token awal
            SarprasWasteSeeder::class, // sumber sampah awal untuk laporan DLH
            // UksWorkUnitSeeder::class,      // UKS Putra & UKS Putri satker units
            PermitTypeSeeder::class,       // master jenis izin (pulang, sakit, dll.)
            StafPerizinanSeeder::class,    // granular permissions for Staf Perizinan
            SchoolSeeder::class,       // master jenis izin (pulang, sakit, dll.)
            DormitorySeeder::class,       // master jenis izin (pulang, sakit, dll.)
            AcademicYearSeeder::class,
            LibraryUnitSeeder::class,           // WAJIB: unit Putra, Putri, dan SD
            CurriculumProfileSeeder::class,    // profil KBC Kemenag & Merdeka Dinas
            CurriculumStructureSeeder::class,   // WAJIB: tingkat, mapel, alokasi jam per satuan pendidikan
            SchoolRombelStructureSeeder::class, // WAJIB: rombel SD/SMP/SMA + tautan mapel ke rombel
            ClassScheduleSlotSeeder::class,    // WAJIB: master jam pelajaran (kegiatan/istirahat/pulang)       // master jenis izin (pulang, sakit, dll.)
            DokumenIsoSeeder::class,       // master jenis izin (pulang, sakit, dll.)

            // Katalog AI — semua model nonaktif, tanpa API key.
            AiProviderSeeder::class,

            // Fondasi Tahfidz (work unit DEP-TAH, paket, rubrik, surah, juz).
            TahfidzFoundationSeeder::class,
            TahfidzBaselineCurriculumSeeder::class, // Muqorror & RPP standar SD/SMP/SMA
        ]);
    }
}
