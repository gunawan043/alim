<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grade_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->uuid('from_study_group_id')
                ->comment('Kelas asal santri di tahun ajaran ini');
            $table->uuid('from_grade_level_id');
            $table->uuid('to_grade_level_id')->nullable()
                ->comment('Tingkat kelas tujuan. NULL jika status = lulus');
            $table->enum('status', [
                'naik_kelas',
                'tinggal_kelas',
                'lulus',
                'mutasi_keluar',
                'mengundurkan_diri',
                'dikeluarkan',
            ]);
            $table->decimal('final_avg_score', 5, 2)->nullable()
                ->comment('Nilai rata-rata akhir semua mata pelajaran');
            $table->integer('total_absent_days')->default(0)
                ->comment('Total hari alpa sepanjang tahun ajaran');
            $table->date('promotion_date');
            $table->string('decree_number', 100)->nullable()
                ->comment('Nomor SK kenaikan kelas / kelulusan');
            $table->text('notes')->nullable();
            $table->uuid('decided_by')
                ->comment('Kepala sekolah yang memutuskan');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('from_study_group_id')->references('id')->on('study_groups');
            $table->foreign('from_grade_level_id')->references('id')->on('grade_levels');
            $table->foreign('to_grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
            $table->foreign('decided_by')->references('id')->on('users');

            // Satu siswa hanya punya satu keputusan per tahun ajaran
            $table->unique(['student_id', 'academic_year_id'], 'unique_promotion_per_year');
            $table->index(['school_id', 'academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grade_promotions');
    }
};
