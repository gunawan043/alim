<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('additional_task_types')) {
            return;
        }

        $jenisGtkId = DB::table('jenis_gtk')
            ->where('nama', 'like', '%Satuan Pendidikan%')
            ->value('id');

        if (! $jenisGtkId) {
            return;
        }

        if (DB::table('additional_task_types')->where('kode', 'guru-koordinator-osipp')->exists()) {
            return;
        }

        $maxUrutan = (int) DB::table('additional_task_types')
            ->where('jenis_gtk_id', $jenisGtkId)
            ->max('urutan');

        DB::table('additional_task_types')->insert([
            'id' => (string) Str::uuid(),
            'jenis_gtk_id' => $jenisGtkId,
            'nama' => 'Guru Koordinator OSIPP',
            'kode' => 'guru-koordinator-osipp',
            'deskripsi' => 'Koordinator pembinaan, administrasi, program kerja, verifikasi kegiatan, dan evaluasi kepengurusan OSIPP.',
            'urutan' => $maxUrutan + 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasTable('additional_task_types')) {
            DB::table('additional_task_types')->where('kode', 'guru-koordinator-osipp')->delete();
        }
    }
};
