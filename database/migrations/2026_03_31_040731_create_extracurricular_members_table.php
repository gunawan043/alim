<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurricular_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('extracurricular_id');
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('role', [
                'anggota',
                'ketua',
                'wakil_ketua',
                'sekretaris',
                'bendahara',
            ])->default('anggota');
            $table->date('join_date');
            $table->date('leave_date')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif', 'lulus'])->default('aktif');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('extracurricular_id')->references('id')->on('extracurriculars')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            // Satu santri hanya bisa terdaftar sekali per ekskul per tahun ajaran
            $table->unique(
                ['extracurricular_id', 'student_id', 'academic_year_id'],
                'unique_member_per_extracurricular_year'
            );
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_members');
    }
};
