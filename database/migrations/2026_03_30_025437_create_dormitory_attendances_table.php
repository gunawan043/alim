<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->uuid('room_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->uuid('recorded_by')->comment('Wali kamar / musyrif yang mencatat');
            $table->date('attendance_date');
            $table->enum('session', ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'])
                  ->comment('Sesi apel / pengecekan kehadiran');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa', 'pulang']);
            $table->text('notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
 
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
 
            // Satu santri, satu sesi, satu tanggal = satu record
            $table->unique(
                ['student_id', 'room_id', 'attendance_date', 'session'],
                'unique_dormitory_attendance_per_session'
            );
            $table->index(['room_id', 'attendance_date', 'session']);
            $table->index(['student_id', 'academic_year_id']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_attendances');
    }
};
