<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_nilai_sumatif', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
 
            // Penilaian Sumatif Harian (S1 - S6)
            $table->decimal('s1', 5, 2)->nullable()->comment('Asesmen Sumatif 1');
            $table->decimal('s2', 5, 2)->nullable()->comment('Asesmen Sumatif 2');
            $table->decimal('s3', 5, 2)->nullable()->comment('Asesmen Sumatif 3');
            $table->decimal('s4', 5, 2)->nullable()->comment('Asesmen Sumatif 4');
            $table->decimal('s5', 5, 2)->nullable()->comment('Asesmen Sumatif 5');
            $table->decimal('s6', 5, 2)->nullable()->comment('Asesmen Sumatif 6');
 
            // Nilai Agregat
            $table->decimal('rs', 5, 2)->nullable()->comment('Rerata Sumatif');
            $table->decimal('sts', 5, 2)->nullable()->comment('Sumatif Tengah Semester');
            $table->decimal('sas', 5, 2)->nullable()->comment('Sumatif Akhir Semester');
            $table->decimal('rsa', 5, 2)->nullable()->comment('Rerata Sumatif Akhir = (STS+SAS)/2');
            $table->decimal('nr_murni', 5, 2)->nullable()->comment('Nilai Raport Murni = (RS+RSA)/2');
            $table->decimal('nr_final', 5, 2)->nullable()->comment('Nilai Raport Final setelah pertimbangan guru');
            $table->string('ket', 100)->nullable()->comment('Keterangan');
            $table->timestamps();
 
            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
 
            $table->unique(['admin_book_id', 'student_id', 'semester'], 'unique_nilai_sumatif_per_student');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_presensi_guru'
            );
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('admin_nilai_sumatif');
    }
};
