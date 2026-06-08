<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan field untuk menyimpan referensi dokumen dari sistem external (recruitment.abuhurairah.id)
     * Dokumen fisik tetap di server external, ALIM hanya menyimpan metadata dan URL.
     */
    public function up(): void
    {
        Schema::table('recruitment_documents', function (Blueprint $table) {
            // ID dokumen di sistem external (untuk mapping dan update)
            $table->string('external_id')->nullable()->after('id');

            // URL lengkap ke dokumen di server external
            $table->text('external_url')->nullable()->after('file_path');

            // Timestamp sinkronisasi terakhir
            $table->timestamp('synced_at')->nullable()->after('updated_at');

            // Index untuk pencarian cepat berdasarkan external_id
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_documents', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn(['external_id', 'external_url', 'synced_at']);
        });
    }
};
