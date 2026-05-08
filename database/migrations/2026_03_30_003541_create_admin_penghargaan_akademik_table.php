<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_penghargaan_akademik', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            // Skala 1–5 per aspek karakter
            $table->tinyInteger('jujur')->nullable()->comment('Poin kejujuran 1-5');
            $table->tinyInteger('disiplin')->nullable()->comment('Poin kedisiplinan 1-5');
            $table->tinyInteger('peduli')->nullable()->comment('Poin kepedulian 1-5');
            $table->tinyInteger('adab')->nullable()->comment('Poin adab 1-5');
            $table->tinyInteger('kehadiran')->nullable()->comment('Poin kehadiran 1-5');
            $table->tinyInteger('keaktifan')->nullable()->comment('Poin keaktifan 1-5');
            $table->timestamps();
 
            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
 
            $table->unique(['admin_book_id', 'student_id', 'semester'], 'unique_penghargaan_per_student');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_presensi_guru'
            );
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('admin_penghargaan_akademik');
    }
};
 
