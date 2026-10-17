<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil pembacaan OMR per nomor soal (hasil AWAL, sebelum koreksi guru).
 *
 * `evidence` menyimpan kekuatan bukti tiap opsi + fitur citra sehingga
 * keputusan mesin dapat diaudit dan dikalibrasi ulang.
 * Koreksi guru disimpan terpisah di ljk_answer_corrections.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_scan_answers')) {
            return;
        }

        Schema::create('ljk_scan_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ljk_scan_id');
            $table->uuid('ljk_exam_id')->nullable();
            $table->uuid('participant_id')->nullable();
            $table->unsignedSmallInteger('nomor');
            $table->uuid('item_id')->nullable();
            $table->uuid('soal_id')->nullable();
            $table->string('jenis', 20)->default('pg');
            $table->json('opsi')->nullable();
            $table->string('status', 24)->default('meragukan');
            $table->string('selected_label', 4)->nullable();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->json('evidence')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('point_awal', 6, 2)->default(0);
            $table->decimal('point_final', 6, 2)->default(0);
            $table->boolean('needs_review')->default(false);
            $table->string('review_action', 16)->default('none');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('teacher_note', 255)->nullable();
            $table->timestamps();

            $table->foreign('ljk_scan_id', 'ljk_scan_answers_scan_fk')
                ->references('id')->on('ljk_scans')->cascadeOnDelete();

            $table->unique(['ljk_scan_id', 'nomor'], 'ljk_scan_answers_scan_nomor_unique');
            $table->index(['ljk_exam_id', 'status'], 'ljk_scan_answers_exam_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_scan_answers');
    }
};
