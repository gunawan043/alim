<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendance_recaps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->tinyInteger('recap_month')->nullable()
                ->comment('1-12 untuk rekap bulanan, NULL untuk rekap per semester');
            $table->year('recap_year');
            $table->integer('total_hadir')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_sakit')->default(0);
            $table->integer('total_alpa')->default(0);
            $table->integer('total_cuti')->default(0);
            $table->integer('total_dinas_luar')->default(0);
            $table->integer('total_jam_hadir')->default(0)
                ->comment('Total jam pelajaran yang benar-benar terlaksana');
            $table->integer('total_jam_jadwal')->default(0)
                ->comment('Total jam pelajaran yang seharusnya berdasarkan jadwal');
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(
                ['teacher_id', 'academic_year_id', 'semester', 'recap_month', 'recap_year'],
                'uniq_tar_teacher_period'
            );

            $table->index(
                ['school_id', 'academic_year_id', 'semester'],
                'idx_tar_school_year_sem'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendance_recaps');
    }
};
