<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log koreksi guru — APPEND-ONLY.
 *
 * Menyimpan nilai sebelum & sesudah, aktor, waktu, dan alasan. Baris pada
 * tabel ini tidak boleh diubah/dihapus agar riwayat pemeriksaan utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_answer_corrections')) {
            return;
        }

        Schema::create('ljk_answer_corrections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ljk_scan_answer_id');
            $table->uuid('ljk_exam_id')->nullable();
            $table->uuid('participant_id')->nullable();
            $table->unsignedSmallInteger('nomor');
            $table->json('before');
            $table->json('after');
            $table->string('reason', 255)->nullable();
            $table->uuid('corrected_by')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->foreign('ljk_scan_answer_id', 'ljk_corrections_answer_fk')
                ->references('id')->on('ljk_scan_answers')->cascadeOnDelete();

            $table->index(['ljk_exam_id', 'nomor'], 'ljk_corrections_exam_nomor_idx');
            $table->index('corrected_by', 'ljk_corrections_actor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_answer_corrections');
    }
};
