<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_health_checkups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->date('checkup_date');
            $table->enum('checkup_type', [
                'rutin',       // Check-up berkala
                'akar',        // Pre-university screening
                'masuk',       // Saat masuk sekolah
                '闻言',       // Laporan dari asrama
            ]);
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->decimal('bmi', 5, 2)->nullable()
                  ->comment('BMI dihitung otomatis dari tinggi/berat');
            $table->enum('bmi_category', [
                'sangat_kurang',
                'kurang',
                'normal',
                'lebih',
                'gemuk',
            ])->nullable();
            $table->decimal('vision_left', 4, 2)->nullable()
                  ->comment('Visus mata kiri, misal 1.0');
            $table->decimal('vision_right', 4, 2)->nullable()
                  ->comment('Visus mata kanan, misal 1.0');
            $table->enum('hearing_status', ['normal', 'kurang', 'tidak_ada'])->
                  default('normal');
            $table->enum('dental_status', ['normal', 'karies', 'gangguan'])->
                  default('normal');
            $table->enum('tb_screening_result', [
                'negatif', 'akur', 'laten', 'aktif_suspect',
            ])->nullable();
            $table->text('tb_notes')->nullable();
            $table->tinyInteger('is_school_entry')->default(0)
                  ->comment('1 = pemeriksaan saat masuk sekolah baru');
            $table->uuid('exam_by')->nullable()
                  ->comment('Petugas yang memeriksa');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('exam_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['student_id', 'academic_year_id', 'checkup_date']);
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'checkup_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_checkups');
    }
};