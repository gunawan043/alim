<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lembar jawaban tercetak per peserta.
 *
 * sheet_code + qr_token (HMAC) = identitas mesin lembar yang divalidasi
 * server. Tidak memuat kunci jawaban. Template dirujuk beserta versinya
 * agar pembacaan memakai geometri yang benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_sheets')) {
            return;
        }

        Schema::create('ljk_sheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ljk_exam_id');
            $table->uuid('participant_id');
            $table->uuid('template_id');
            $table->unsignedInteger('template_version')->default(1);
            $table->string('sheet_code', 32)->unique('ljk_sheets_code_unique');
            $table->string('qr_token', 255);
            $table->unsignedTinyInteger('pages')->default(1);
            $table->string('status', 20)->default('generated');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->foreign('ljk_exam_id', 'ljk_sheets_exam_fk')
                ->references('id')->on('ljk_exams')->cascadeOnDelete();
            $table->foreign('participant_id', 'ljk_sheets_participant_fk')
                ->references('id')->on('ljk_participants')->cascadeOnDelete();
            $table->foreign('template_id', 'ljk_sheets_template_fk')
                ->references('id')->on('ljk_templates')->restrictOnDelete();

            $table->unique('participant_id', 'ljk_sheets_participant_unique');
            $table->index('status', 'ljk_sheets_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_sheets');
    }
};
