<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemetaan identitas eksternal (WhatsApp) ke personel Core yang sudah
     * diverifikasi. Command persetujuan dari ALIM Connect TIDAK boleh memakai
     * `actor_core_person_id` dari payload; aktor selalu diselesaikan lewat
     * tabel ini.
     *
     * Aditif dan backward-compatible: tidak menyentuh tabel lain.
     */
    public function up(): void
    {
        Schema::create('connect_actor_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel', 20)->default('whatsapp');
            $table->string('external_identity', 191)
                ->comment('Nomor WhatsApp ternormalisasi, mis. 6281234567890');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('display_name', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_identity'], 'connect_actor_identity_unique');
            $table->index(['user_id', 'is_active'], 'connect_actor_identity_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_actor_identities');
    }
};
