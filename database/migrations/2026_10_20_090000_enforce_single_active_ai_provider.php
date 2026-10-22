<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pastikan hanya satu provider AI yang aktif. Jika sebelumnya ada
     * beberapa baris aktif, pertahankan provider default (atau provider
     * aktif pertama) dan nonaktifkan sisanya.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            return;
        }

        $active = DB::table('ai_provider_settings')
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->first()
            ?: DB::table('ai_provider_settings')
                ->where('is_active', true)
                ->orderBy('name')
                ->first();

        if (! $active) {
            return;
        }

        DB::table('ai_provider_settings')
            ->where('id', '!=', $active->id)
            ->update(['is_active' => false, 'is_default' => false]);

        DB::table('ai_provider_settings')
            ->where('id', $active->id)
            ->update(['is_active' => true, 'is_default' => true]);
    }

    public function down(): void
    {
        // Tidak ada rollback: status aktif lama tidak aman dikembalikan.
    }
};
