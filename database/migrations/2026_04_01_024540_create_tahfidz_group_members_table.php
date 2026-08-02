<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tahfidz_group_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->date('join_date');
            $table->date('leave_date')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif', 'pindah_halaqah', 'lulus'])->default('aktif');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->unique(['tahfidz_group_id', 'student_id', 'academic_year_id'], 'unique_member_per_group_year');
            $table->index(['student_id', 'academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_group_members');
    }
};
