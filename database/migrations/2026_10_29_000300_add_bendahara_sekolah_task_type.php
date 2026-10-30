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

        $existing = DB::table('additional_task_types')
            ->where('kode', 'bendahara-sekolah')
            ->first();

        if ($existing) {
            DB::table('additional_task_types')
                ->where('id', $existing->id)
                ->update([
                    'jenis_gtk_id' => $jenisGtk->id,
                    'nama' => 'Bendahara Sekolah',
                    'is_active' => 1,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('additional_task_types')->insert([
            'id' => (string) Str::uuid(),
            'jenis_gtk_id' => $jenisGtk->id,
            'nama' => 'Bendahara Sekolah',
            'kode' => 'bendahara-sekolah',
            'deskripsi' => 'Tugas tambahan pengelolaan keuangan sekolah dan integrasi POS.',
            'urutan' => 20,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('additional_task_types')
            ->where('kode', 'bendahara-sekolah')
            ->delete();
    }
};
