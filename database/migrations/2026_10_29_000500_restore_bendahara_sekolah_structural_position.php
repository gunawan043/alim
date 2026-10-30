<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $jenisGtk = DB::table('jenis_gtk')
            ->where('nama', 'like', '%Satuan Pendidikan%')
            ->first();

        if (! $jenisGtk) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'Satuan Pendidikan')->value('id');
        $domainId = DB::table('domains')
            ->where('code', 'satuan-pendidikan')
            ->value('id');

        $code = 'tenaga_kependidikan_satuan_pendidikan_bendah_05e6e';

        DB::table('structural_positions')->updateOrInsert(
            ['code' => $code],
            [
                'id' => optional(DB::table('structural_positions')->where('code', $code)->first())->id ?: (string) Str::uuid(),
                'name' => 'Bendahara Sekolah',
                'level' => 'pondok',
                'hierarchy_level' => 5,
                'jenis_gtk_id' => $jenisGtk->id,
                'kategori' => 'Jabatan',
                'description' => 'Jabatan pengelolaan keuangan sekolah dan integrasi POS.',
                'role_id' => $roleId,
                'domain_id' => $domainId,
                'urutan' => 20,
                'is_active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Jangan hapus: Bendahara Sekolah juga merupakan jabatan resmi.
    }
};
