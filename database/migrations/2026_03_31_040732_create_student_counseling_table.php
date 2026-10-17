<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_counseling', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('counselor_id')->comment('Guru BK yang menangani');
            $table->uuid('academic_year_id');
            $table->date('session_date');
            $table->integer('session_number')->default(1)
                ->comment('Pertemuan ke-N dengan santri yang sama terkait kasus ini');
            $table->enum('session_type', [
                'akademik',
                'pribadi',
                'sosial',
                'karir',
                'keluarga',
                'lainnya',
            ]);
            $table->enum('referral_source', [
                'mandiri',       // Datang sendiri
                'wali_kelas',
                'guru_mapel',
                'orang_tua',
                'kepala_sekolah',
                'asrama',
            ])->default('mandiri');
            $table->text('complaint')->nullable()->comment('Masalah atau keluhan yang disampaikan');
            $table->text('assessment')->nullable()->comment('Penilaian / analisis guru BK');
            $table->text('action_taken')->nullable()->comment('Tindakan yang diambil dalam sesi ini');
            $table->text('follow_up_plan')->nullable()->comment('Rencana tindak lanjut');
            $table->date('follow_up_date')->nullable();
            $table->enum('outcome', [
                'selesai',
                'perlu_tindak_lanjut',
                'dirujuk_orang_tua',
                'dirujuk_profesional',
            ])->default('perlu_tindak_lanjut');
            $table->tinyInteger('is_confidential')->default(0)
                ->comment('1 = rahasia, hanya bisa diakses BK dan Kepala Sekolah');
            $table->tinyInteger('parent_informed')->default(0);
            $table->timestamp('parent_informed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('counselor_id')->references('id')->on('users');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->index(['student_id', 'academic_year_id']);
            $table->index(['counselor_id', 'session_date']);
            $table->index(['school_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_counseling');
    }
};
