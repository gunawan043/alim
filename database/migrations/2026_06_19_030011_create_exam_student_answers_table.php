<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_student_answers', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('exam_attempt_id', 36);
            $table->char('soal_id', 36);
            $table->integer('nomor_urut')->default(0);
            $table->char('selected_option_id', 36)->nullable();
            $table->longText('essay_answer')->nullable();
            $table->tinyInteger('is_correct')->nullable()->comment('0=false,1=true,NULL=manual/grading required');
            $table->decimal('point_awal', 4, 2)->default(0);
            $table->decimal('point_final', 4, 2)->default(0);
            $table->boolean('graded_by_teacher')->default(false);
            $table->text('teacher_notes')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->foreign('exam_attempt_id')->references('id')->on('exam_attempts')->onDelete('cascade');
            $table->foreign('soal_id')->references('id')->on('soal')->onDelete('restrict');
            $table->foreign('selected_option_id')->references('id')->on('soal_options')->onDelete('set null');

            $table->index(['exam_attempt_id', 'nomor_urut'], 'exam_student_answers_idx_attempt_order');
            $table->index(['soal_id', 'exam_attempt_id'], 'exam_student_answers_idx_soal_attempt');
            $table->index('is_correct', 'exam_student_answers_idx_correct');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_student_answers');
    }
};
