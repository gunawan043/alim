<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_class_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id')->index();
            $table->uuid('academic_year_id')->nullable()->index();
            $table->uuid('study_group_id')->nullable()->index();
            $table->uuid('jadwal_kbm_id')->nullable()->index()
                ->comment('Jadwal KBM yang menjadi acuan absensi ini');
            $table->uuid('teacher_id')->index();
            $table->uuid('qr_token_id')->nullable()->index()
                ->comment('Token QR yang di-scan (masuk)');
            $table->date('attendance_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->time('actual_time_in')->nullable();
            $table->time('actual_time_out')->nullable();
            $table->time('scheduled_break_time')->nullable()->default('00:00:00');
            $table->integer('late_minutes')->default(0)
                ->comment('Terlambat berapa menit dari scheduled_start_time');
            $table->integer('early_leave_minutes')->default(0)
                ->comment('Pulang berapa menit sebelum scheduled_end_time');
            $table->integer('duration_minutes')->default(0)
                ->comment('Durasi mengajar aktual dalam menit');
            $table->enum('status_masuk', ['hadir', 'terlambat', 'izin', 'sakit', 'alpa', 'cuti', 'dinas_luar'])
                ->default('hadir');
            $table->enum('status_keluar', [
                'belum_keluar',
                'selesai',
                'keluar_cepat',
                'tidak_keluar',
            ])->default('belum_keluar')
                ->comment('belum_keluar = belum scan keluar / belum check-out');
            $table->uuid('checkout_qr_token_id')->nullable()->index();
            $table->boolean('is_substitute')->default(false)
                ->comment('True = guru pengganti (bukan jadwal asli)');
            $table->uuid('recorded_by')->nullable()
                ->comment('Guru yang scan / Waka yang input manual');
            $table->text('notes')->nullable();
            $table->timestamp('verified_by_waka_at')->nullable();
            $table->uuid('verified_by_waka')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->nullOnDelete();
            $table->foreign('jadwal_kbm_id')->references('id')->on('jadwal_kbms')->nullOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('qr_token_id')->references('id')->on('qr_class_tokens')->nullOnDelete();
            $table->foreign('checkout_qr_token_id')->references('id')->on('qr_class_tokens')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by_waka')->references('id')->on('users')->nullOnDelete();

            // Satu absensi per jadwal per guru per hari
            $table->unique(
                ['teacher_id', 'jadwal_kbm_id', 'attendance_date'],
                'uniq_tca_teacher_jadwal_date'
            );
            $table->index(['teacher_id', 'attendance_date'], 'idx_tca_teacher_date');
            $table->index(['school_id', 'attendance_date'], 'idx_tca_school_date');
            $table->index(['study_group_id', 'attendance_date'], 'idx_tca_sg_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_class_attendances');
    }
};
