<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->uuid('role_id')->nullable()->after('jenis_gtk_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        // ✅ Role baru untuk arsitektur Role + Jabatan + Tugas Tambahan
        $newRoles = [
            ['id' => Str::uuid(), 'name' => 'Pimpinan',       'guard_name' => 'web', 'level' => 2],
            ['id' => Str::uuid(), 'name' => 'Pendidikan',      'guard_name' => 'web', 'level' => 7],
            ['id' => Str::uuid(), 'name' => 'Asrama',          'guard_name' => 'web', 'level' => 15],
            ['id' => Str::uuid(), 'name' => 'UKS',             'guard_name' => 'web', 'level' => 18],
            ['id' => Str::uuid(), 'name' => 'Tahfidz',         'guard_name' => 'web', 'level' => 16],
            ['id' => Str::uuid(), 'name' => 'Bahasa',          'guard_name' => 'web', 'level' => 17],
            ['id' => Str::uuid(), 'name' => 'Perpustakaan',    'guard_name' => 'web', 'level' => 19],
            ['id' => Str::uuid(), 'name' => 'Keamanan',        'guard_name' => 'web', 'level' => 24],
            ['id' => Str::uuid(), 'name' => 'Humas Personalia', 'guard_name' => 'web', 'level' => 13],
            ['id' => Str::uuid(), 'name' => 'Rumah Tangga',    'guard_name' => 'web', 'level' => 20],
            ['id' => Str::uuid(), 'name' => 'Keuangan',        'guard_name' => 'web', 'level' => 25],
            ['id' => Str::uuid(), 'name' => 'Teknologi Informasi', 'guard_name' => 'web', 'level' => 22],
            ['id' => Str::uuid(), 'name' => 'Gizi Logistik',   'guard_name' => 'web', 'level' => 21],
        ];

        foreach ($newRoles as $r) {
            $exists = DB::table('roles')->where('name', $r['name'])->where('guard_name', 'web')->exists();
            if (! $exists) {
                DB::table('roles')->insert([
                    'id' => (string) $r['id'],
                    'name' => $r['name'],
                    'guard_name' => 'web',
                    'level' => $r['level'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Mapping role_id untuk setiap posisi berdasarkan jenis GTK-nya
        $mapping = [
            // Pimpinan Pondok → role: Pimpinan
            ['nama' => 'Mudir',                   'role' => 'Pimpinan'],
            ['nama' => 'Wakil Mudir I',           'role' => 'Pimpinan'],
            ['nama' => 'Wakil Mudir II',          'role' => 'Pimpinan'],

            // Satuan Pendidikan → role: Pendidikan
            ['nama' => 'Kepala Satuan Pendidikan', 'role' => 'Pendidikan'],
            ['nama' => 'Wakil Kepala Satuan Pendidikan', 'role' => 'Pendidikan'],
            ['nama' => 'Kepala Tata Usaha',       'role' => 'Pendidikan'],
            ['nama' => 'Staf Tata Usaha',         'role' => 'Pendidikan'],
            ['nama' => 'Guru Hadits',             'role' => 'Pendidikan'],
            ['nama' => 'Guru Umum',               'role' => 'Pendidikan'],
            ['nama' => 'Guru Agama',              'role' => 'Pendidikan'],
            ['nama' => 'Wali Kelas',              'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Kurikulum',   'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Kesiswaan',   'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Sarpras Sekolah', 'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Ekstrakurikuler', 'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Guru Bahasa Arab', 'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Guru Umum',   'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Guru Agama',  'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Guru Hadits', 'role' => 'Pendidikan'],
            ['nama' => 'Koordinator Guru Tahfidz', 'role' => 'Pendidikan'],

            // Departemen Tahfidz → role: Tahfidz
            ['nama' => 'Kepala Departemen Tahfidz', 'role' => 'Tahfidz'],
            ['nama' => 'Admin Departemen Tahfidz', 'role' => 'Tahfidz'],
            ['nama' => 'Guru Tahfidz',            'role' => 'Tahfidz'],

            // Departemen Bahasa → role: Bahasa
            ['nama' => 'Kepala Departemen Bahasa', 'role' => 'Bahasa'],
            ['nama' => 'Admin Departemen Bahasa', 'role' => 'Bahasa'],
            ['nama' => 'Guru Bahasa Arab',        'role' => 'Bahasa'],

            // Asrama → role: Asrama
            ['nama' => 'Kepala Asrama',           'role' => 'Asrama'],
            ['nama' => 'Wakil Kepala Asrama',     'role' => 'Asrama'],
            ['nama' => 'Tata Usaha Asrama',       'role' => 'Asrama'],
            ['nama' => 'Staf Asrama',             'role' => 'Asrama'],
            ['nama' => 'Wali Kamar',              'role' => 'Asrama'],
            ['nama' => 'Musyrif',                 'role' => 'Asrama'],
            ['nama' => 'Musyrifah',               'role' => 'Asrama'],
            ['nama' => 'Pembina Asrama',          'role' => 'Asrama'],

            // UKS → role: UKS
            ['nama' => 'Kepala UKS',              'role' => 'UKS'],
            ['nama' => 'Staf UKS',                'role' => 'UKS'],

            // Sarana dan Prasarana → role: Rumah Tangga
            ['nama' => 'Kepala Unit Sarana dan Prasarana', 'role' => 'Rumah Tangga'],
            ['nama' => 'Koordinator Sarana dan Prasarana', 'role' => 'Rumah Tangga'],
            ['nama' => 'Staf Sarana dan Prasarana', 'role' => 'Rumah Tangga'],

            // Keuangan → role: Keuangan
            ['nama' => 'Kepala Unit Keuangan',    'role' => 'Keuangan'],
            ['nama' => 'Staf Keuangan',           'role' => 'Keuangan'],

            // Keamanan → role: Keamanan
            ['nama' => 'Kepala Satuan Keamanan',  'role' => 'Keamanan'],
            ['nama' => 'Anggota Satuan Keamanan', 'role' => 'Keamanan'],
        ];

        foreach ($mapping as $item) {
            $roleId = DB::table('roles')->where('name', $item['role'])->value('id');
            if ($roleId) {
                DB::table('positions')
                    ->where('nama', $item['nama'])
                    ->update(['role_id' => $roleId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
