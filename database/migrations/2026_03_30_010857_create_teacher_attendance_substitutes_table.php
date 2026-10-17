<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendance_substitutes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_attendance_id')
                ->comment('Absensi guru yang tidak hadir');
            $table->uuid('substitute_teacher_id')
                ->comment('Guru yang mengisi jam pengganti');
            $table->uuid('class_schedule_id');
            $table->date('substitute_date');
            $table->time('time_start');
            $table->time('time_end');
            $table->enum('substitute_type', [
                'guru_lain',        // Digantikan guru mata pelajaran lain
                'piket',            // Diisi guru piket
                'tugas_mandiri',    // Siswa diberi tugas mandiri
                'digabung',         // Digabung dengan kelas lain
            ]);
            $table->text('task_given')->nullable()
                ->comment('Tugas atau materi yang diberikan kepada siswa');
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('teacher_attendance_id')
                ->references('id')->on('teacher_attendances')->cascadeOnDelete();
            $table->foreign('substitute_teacher_id')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('class_schedule_id')
                ->references('id')->on('class_schedules')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(
                ['substitute_teacher_id', 'substitute_date'],
                'idx_tas_teacher_date'
            );
            $table->index('teacher_attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendance_substitutes');
    }
};
