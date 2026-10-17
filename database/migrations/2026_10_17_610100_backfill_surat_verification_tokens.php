<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['surat_masuk', 'surat_keluar'] as $table) {
            DB::table($table)
                ->whereNull('verification_token')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            'verification_token' => (string) Str::uuid(),
                        ]);
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        // Token tidak dihapus agar tautan verifikasi lama tetap valid.
    }
};
