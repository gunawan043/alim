<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_health_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->date('record_date');
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->enum('bmi_category', [
                'sangat_kurang', // <-3 SD WHO
                'kurang',        // -3 SD s/d -2 SD
                'normal',        // -2 SD s/d +1 SD
                'lebih',         // +1 SD s/d +2 SD
                'gemuk',         // >+2 SD
            ])->nullable();
            $table->enum('measurement_session', [
                'bulanan',
                'caturwulan',
                'akhir_semester',
                'medical_checkup',
            ])->default('bulanan');
            $table->uuid('measured_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('measured_by')->references('id')->on('users')->nullOnDelete();

            // unik: satu record per siswa per tahun ajaran per tanggal
            $table->unique(['student_id', 'academic_year_id', 'record_date']);
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_metrics');
    }
};