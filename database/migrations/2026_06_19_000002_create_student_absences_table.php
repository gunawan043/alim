<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel registrasi absensi per siswa per rombel per tahun ajaran.
     *
     * Berfungsi sebagai "enrollment" siswa ke dalam sistem absensi rombel
     * sehingga setiap siswa yang baru masuk rombel otomatis terdaftar di
     * pipeline absensi (hadir/sakit/izin/alpa akan tercatat di
     * admin_presensi_harian).
     *
     * Idempotency key: (student_id, study_group_id, academic_year_id)
     * sehingga event yang berulang tidak akan menggandakan baris.
     */
    public function up(): void
    {
        Schema::create('student_absences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('semester', ['ganjil', 'genap']);

            // Status registrasi
            // active    : siswa aktif di rombel, absensi akan terekam
            // leave     : siswa keluar dari rombel di tengah semester
            // graduated : siswa naik / lulus dari rombel ini
            $table->enum('enrollment_status', ['active', 'leave', 'graduated'])
                ->default('active');

            $table->date('enrolled_at')->nullable();
            $table->date('left_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['student_id', 'study_group_id', 'academic_year_id', 'semester'],
                'unique_student_absence_per_rombel_per_ta_per_sem'
            );
            $table->index(['study_group_id', 'academic_year_id', 'semester'], 'idx_absence_pipeline');
            $table->index('enrollment_status', 'idx_absence_enrollment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_absences');
    }
};
