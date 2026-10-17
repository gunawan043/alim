<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak sinkronisasi nilai LJK → modul nilai resmi (admin_nilai_sumatif).
 *
 * `payload_hash` = sidik jari hasil final; sinkronisasi dengan hash yang
 * sama tidak diulang (idempotent). payload_hash berbeda = resync setelah
 * koreksi, tercatat sebagai baris baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_sync_logs')) {
            return;
        }

        Schema::create('ljk_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ljk_exam_id');
            $table->uuid('admin_book_id')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->string('action', 24)->default('sync');
            $table->unsignedSmallInteger('students_count')->default(0);
            $table->unsignedSmallInteger('success_count')->default(0);
            $table->unsignedSmallInteger('skipped_count')->default(0);
            $table->char('payload_hash', 64);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('ljk_exam_id', 'ljk_sync_logs_exam_fk')
                ->references('id')->on('ljk_exams')->cascadeOnDelete();

            $table->index(['ljk_exam_id', 'action'], 'ljk_sync_logs_exam_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_sync_logs');
    }
};
