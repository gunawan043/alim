<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tahfidz_uthq_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('registration_id');
            $table->uuid('student_id');
            $table->uuid('uthq_event_id');
            $table->uuid('uthq_category_id');
            $table->enum('round', ['audisi', 'final'])
                  ->comment('Babak audisi atau final');
            $table->uuid('evaluator_id');
            $table->date('assessment_date');
            $table->string('materi_hafalan', 191)->nullable();
            $table->text('soal')->nullable()->comment('Pertanyaan/soal yang diberikan — sesuai kolom SOAL Gambar 4');
            // Komponen sama dengan tasmi'an: Tahfizh(40)+Tajwid(30)+Fashohah(30)
            $table->decimal('tahfizh_score', 5, 2)->nullable();
            $table->decimal('tajwid_score', 5, 2)->nullable();
            $table->decimal('fashohah_score', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->integer('ranking')->nullable()->comment('Peringkat di babak ini');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->foreign('registration_id')->references('id')->on('tahfidz_uthq_registrations')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('uthq_event_id')->references('id')->on('tahfidz_uthq_events')->cascadeOnDelete();
            $table->foreign('uthq_category_id')->references('id')->on('tahfidz_uthq_categories')->cascadeOnDelete();
            $table->foreign('evaluator_id')->references('id')->on('users');
            $table->unique(['registration_id', 'round'], 'unique_assessment_per_round');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_uthq_assessments');
    }
};
