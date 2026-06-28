<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix data consistency so the Nilai index page can render kelas, mapel, dan siswa.
     *
     * Issues:
     * 1. Semua 29 TeacherAdminBook di SD IT Putra Abu Hurairah Mataram (f6d943eb-...)
     *    berstatus is_active=0 sehingga query index memfilter semuanya keluar.
     *    Default migrasi adalah 1, sehingga kondisi ini adalah regresi data seeding.
     * 2. Lima study_group_id yang direferensikan oleh TeacherAdminBook tidak ada di
     *    tabel study_groups (mismatch FK), sehingga relasi studyGroup selalu NULL
     *    dan view tidak bisa menampilkan nama rombel / wali kelas.
     *
     * Fix:
     * 1. Set is_active=1 untuk semua TeacherAdminBook.
     * 2. Rekonstruksi 5 StudyGroup yang hilang dengan id yang sama persis,
     *    school/AY/tingkat disalin dari book, agar relasi tetap utuh.
     * 3. TIDAK menyentuh StudentClassHistory — jika Anda ingin kelas memiliki siswa,
     *    assign siswa lewat alur Student → Class History secara manual.
     */
    public function up(): void
    {
        // Guard: this is a data-fix migration for one specific school's seed data
        // (SD IT Putra Abu Hurairah Mataram). The hardcoded school_id, AY id, and
        // grade_level_ids below only exist in the production seed; test DBs and
        // other environments may not have them, so skip entirely if the school is
        // missing or the table is empty.
        $schoolId = 'f6d943eb-6712-4050-9462-714da766cc2e';
        $ayId = '3accbda9-3856-4f3e-a0cc-82cb7df8b3f1';

        $schoolExists = DB::table('schools')->where('id', $schoolId)->exists();
        $ayExists = DB::table('academic_years')->where('id', $ayId)->exists();

        if (! $schoolExists || ! $ayExists) {
            return;
        }

        $bookCount = (int) DB::table('teacher_admin_books')->count();
        if ($bookCount === 0) {
            return;
        }

        // 1) Aktifkan semua Buku Admin Guru yang nonaktif
        $updated = DB::table('teacher_admin_books')
            ->where('is_active', 0)
            ->update(['is_active' => 1, 'updated_at' => now()]);

        // 2) Rekonstruksi 5 StudyGroup yang hilang.
        //    Dipilih berdasarkan analisis 2026-06-17 terhadap data SD Putra.
        $schoolId = 'f6d943eb-6712-4050-9462-714da766cc2e'; // SD IT Putra Abu Hurairah Mataram
        $ayId = '3accbda9-3856-4f3e-a0cc-82cb7df8b3f1'; // 2025/2026

        // id study_group yang harus direkonstruksi + tingkat (sesuai pola nama SD)
        $missingGroups = [
            '072223cb-c8e1-4d75-b64b-02a30187cd3d' => [
                'grade_level_id' => '0f5698a1-f9ae-4f16-ac6a-89f2edfe4ca4', // placeholder, fixed below
                'name' => 'III-A',
            ],
            '45c9cc35-8c3b-4300-8eef-896b56de2461' => ['name' => 'III-B'],
            '4b48fd8a-db62-4190-838e-9da3fe26ccd5' => ['name' => 'IV-D'],
            '6f1d9a28-d4d0-49b6-8658-846a2e0c8716' => ['name' => 'V-D'],
            '80de3619-d741-431b-9be4-b90aef14b47d' => ['name' => 'VI-D'],
        ];

        // Tingkat (grade_level) untuk SD Putra: 1..6
        $gradeLevels = [
            1 => 'db6d0372-ee43-4e3e-8e31-7bba6c912841',
            2 => '50e4ea9a-4e9b-48c5-bf35-eb68212b7b67',
            3 => '75e5de10-2e5a-453a-9a57-a93097d6f0bf',
            4 => 'e8294cb1-dd32-4c2e-9bcd-3bc0ffa28cfa',
            5 => '246c2984-712a-416c-a6ea-eaa7eb35de8b',
            6 => '39e1bc8e-2cc3-44c0-81f1-ed0443627224',
        ];

        $nameToLevel = [
            'III-A' => 3, 'III-B' => 3,
            'IV-D' => 4,
            'V-D' => 5,
            'VI-D' => 6,
        ];

        foreach ($missingGroups as $id => $meta) {
            $level = $nameToLevel[$meta['name']] ?? null;
            if ($level === null) {
                continue;
            }
            $gradeLevelId = $gradeLevels[$level];

            // Hanya insert jika belum ada (idempotent)
            $exists = DB::table('study_groups')->where('id', $id)->exists();
            if ($exists) {
                continue;
            }

            DB::table('study_groups')->insert([
                'id' => $id,
                'school_id' => $schoolId,
                'academic_year_id' => $ayId,
                'grade_level_id' => $gradeLevelId,
                'name' => $meta['name'],
                'code' => $meta['name'],
                'capacity' => 32,
                'curriculum_type' => 'merdeka',
                'shift' => 'pagi',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Safe no-op: dropping 5 study_groups that may have been re-created
        // by the user's manual process, cascading to books, schedules, etc.
        // This migration is intentionally not roll-backable after it has
        // touched production data.
    }
};
