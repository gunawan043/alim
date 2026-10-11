<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pengiriman WhatsApp (sukses/gagal/log manual) —
 * untuk halaman pengaturan & audit notifikasi wali.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wa_message_logs')) {
            Schema::create('wa_message_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('phone', 30);
                $table->string('event', 50)->nullable();
                $table->string('reference_type', 50)->nullable();
                $table->uuid('reference_id')->nullable();
                $table->string('provider', 30)->nullable();
                $table->string('status', 20)->default('logged'); // sent|failed|logged
                $table->text('message')->nullable();
                $table->text('error')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['event', 'status']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_message_logs');
    }
};
