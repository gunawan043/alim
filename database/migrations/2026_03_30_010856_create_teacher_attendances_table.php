<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->uuid('teacher_id');
            $table->uuid('class_schedule_id')
                  ->comment('Jadwal yang menjadi acuan absensi ini');
            $table->date('attendance_date');
            $table->time('scheduled_time_start')->comment('Jam jadwal seharusnya');
            $table->time('scheduled_time_end');
            $table->time('actual_time_in')->nullable()->comment('Jam aktual guru masuk kelas');
            $table->time('actual_time_out')->nullable()->comment('Jam aktual guru keluar kelas');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa', 'cuti', 'dinas_luar']);
            $table->tinyInteger('is_substituted')->default(0)
                  ->comment('1 = jam ini diisi guru pengganti');
            $table->uuid('recorded_by')
                  ->comment('Guru piket atau TU yang mencatat');
            $table->text('notes')->nullable();
            $table->timestamps();
 
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('class_schedule_id')->references('id')->on('class_schedules')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
 
            $table->unique(
                ['teacher_id', 'class_schedule_id', 'attendance_date'],
                'uniq_ta_teacher_schedule_date'
            );

            $table->index(
                ['school_id', 'attendance_date'],
                'idx_ta_school_date'
            );

            $table->index(
                ['teacher_id', 'attendance_date'],
                'idx_ta_teacher_date'
            );

            $table->index(
                ['teacher_id', 'academic_year_id', 'semester'],
                'idx_ta_teacher_year_sem'
            );
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};