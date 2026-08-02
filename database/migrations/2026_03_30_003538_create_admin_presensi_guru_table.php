<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_presensi_guru', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa', 'pengganti']);
            $table->uuid('substitute_teacher_id')->nullable()->comment('Guru pengganti jika tidak hadir');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('substitute_teacher_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['admin_book_id', 'attendance_date'], 'unique_guru_attendance_per_day');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_presensi_guru'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_presensi_guru');
    }
};
