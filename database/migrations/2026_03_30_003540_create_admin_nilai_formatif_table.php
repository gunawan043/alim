<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_nilai_formatif', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->decimal('skor_lkpd', 5, 2)->nullable()->comment('Mengerjakan LKPD');
            $table->decimal('skor_diskusi', 5, 2)->nullable()->comment('Diskusi Kelompok');
            $table->decimal('skor_kuis', 5, 2)->nullable()->comment('Kuis / Tes Singkat');
            $table->decimal('skor_antarteman', 5, 2)->nullable()->comment('Penilaian Antarteman');
            $table->timestamps();

            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(['admin_book_id', 'student_id', 'semester'], 'unique_nilai_formatif_per_student');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_nilai_formatif'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_nilai_formatif');
    }
};
