<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tujuan_pembelajaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('subject_id');
            $table->uuid('grade_level_id')->nullable();
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->string('fase', 5)->nullable();
            $table->string('kode_tp', 20);
            $table->text('deskripsi');
            $table->string('elemen', 100)->nullable();
            $table->unsignedInteger('alokasi_waktu')->default(2);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['subject_id', 'grade_level_id', 'academic_year_id', 'semester', 'kode_tp'],
                'tp_unique_per_subject_grade_ay_semester'
            );
            $table->index(['school_id', 'subject_id', 'academic_year_id', 'semester'], 'tp_school_subject_ay_semester_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tujuan_pembelajaran');
    }
};
