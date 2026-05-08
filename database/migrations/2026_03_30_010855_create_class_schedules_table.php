<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->uuid('study_group_id');
            $table->uuid('subject_id');
            $table->uuid('teacher_id');
            // FK ke SK guru — jalur: class_schedules → teaching_assignments → institution_decrees
            $table->uuid('teaching_assignment_id')->nullable()
                  ->comment('FK ke teaching_assignments.id (terhubung SK guru via decree_id)');
            $table->uuid('schedule_slot_id')
                  ->comment('FK ke class_schedule_slots.id');
            $table->tinyInteger('day_of_week')->comment('1=Senin ... 6=Sabtu');
            $table->time('time_start');
            $table->time('time_end');
            $table->string('room', 50)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->date('effective_date')->nullable()->comment('Jadwal berlaku mulai tanggal');
            $table->date('end_date')->nullable()->comment('Jadwal berlaku sampai tanggal');
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('teaching_assignment_id')
                  ->references('id')->on('teaching_assignments')->nullOnDelete();
            $table->foreign('schedule_slot_id')
                  ->references('id')->on('class_schedule_slots')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
 
            // Satu slot di satu kelas tidak boleh diisi dua mapel sekaligus
            $table->unique(
                ['study_group_id', 'day_of_week', 'schedule_slot_id', 'academic_year_id', 'semester'],
                'unique_schedule_per_class_slot'
            );
            // Satu guru tidak boleh dijadwalkan di dua kelas berbeda di jam yang sama
            $table->unique(
                ['teacher_id', 'day_of_week', 'schedule_slot_id', 'academic_year_id', 'semester'],
                'unique_schedule_per_teacher_slot'
            );
            $table->index(
                ['school_id', 'academic_year_id', 'semester', 'day_of_week'],
                'idx_cs_school_year_sem_day'
            );
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};