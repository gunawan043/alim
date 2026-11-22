<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time SSO handoff codes from ALIM Core to ALIM Connect.
     */
    public function up(): void
    {
        Schema::create('connect_sso_handoffs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->string('audience', 32);
            $table->string('scope', 100)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip', 45)->nullable();
            $table->string('used_user_agent', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'connect_sso_user_created_idx');
            $table->index('expires_at', 'connect_sso_expires_idx');
            $table->index('used_at', 'connect_sso_used_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_sso_handoffs');
    }
};
