<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembiasaan_pagi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);

            // Pembiasaan Pagi
            $table->decimal('skor_doa', 5, 2)->nullable()->comment('Doa');
            $table->decimal('skor_hiwar', 5, 2)->nullable()->comment('Hiwar');
            $table->decimal('skor_conversation', 5, 2)->nullable()->comment('Conversation');
            $table->timestamps();

            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(['admin_book_id', 'student_id', 'semester'], 'unique_pembiasaan_per_student');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_pembiasaan_pagi'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembiasaan_pagi');
    }
};
