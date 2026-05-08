<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_presensi_mapel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->date('attendance_date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa', 'pengganti']);
            $table->text('notes')->nullable();
            $table->timestamps();
 
            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
 
            $table->unique(['admin_book_id', 'attendance_date'], 'unique_guru_attendance_per_day');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_presensi_guru'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_presensi_mapel');
    }
};
