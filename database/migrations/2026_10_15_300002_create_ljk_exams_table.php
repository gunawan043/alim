<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesi ujian LJK — turunan dari Paket Soal + konfigurasi ujian ALIM.
 *
 * Tidak menyalin isi soal. Yang disimpan hanyalah referensi:
 * paket_soal_id + versi + snapshot_at + paket_digest (sidik jari naskah
 * beku) agar lembar yang sudah dicetak tetap tertelusur ke versi paket
 * yang benar meskipun paket berubah setelah LJK diterbitkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_exams')) {
            return;
        }

        Schema::create('ljk_exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique('ljk_exams_code_unique');
            $table->uuid('school_id')->nullable();
            $table->uuid('academic_year_id')->nullable();
            $table->string('semester', 10)->default('ganjil');
            $table->uuid('admin_book_id')->nullable();
            $table->uuid('paket_soal_id');
            $table->unsignedInteger('paket_version')->default(1);
            $table->timestamp('paket_snapshot_at')->nullable();
            $table->char('paket_digest', 64)->nullable();
            $table->uuid('study_group_id')->nullable();
            $table->uuid('subject_id')->nullable();
            $table->uuid('template_id');
            $table->string('jenis_ujian', 24)->default('ulangan_harian');
            $table->string('judul', 150);
            $table->date('tanggal')->nullable();
            $table->unsignedSmallInteger('jumlah_soal')->default(0);
            $table->json('skor_config')->nullable();
            $table->json('nilai_target')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('instruksi')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('paket_soal_id', 'ljk_exams_paket_fk')
                ->references('id')->on('paket_soal')->restrictOnDelete();
            $table->foreign('template_id', 'ljk_exams_template_fk')
                ->references('id')->on('ljk_templates')->restrictOnDelete();
            $table->foreign('admin_book_id', 'ljk_exams_book_fk')
                ->references('id')->on('teacher_admin_books')->nullOnDelete();
            $table->foreign('study_group_id', 'ljk_exams_group_fk')
                ->references('id')->on('study_groups')->nullOnDelete();
            $table->foreign('school_id', 'ljk_exams_school_fk')
                ->references('id')->on('schools')->nullOnDelete();

            $table->index(['school_id', 'status'], 'ljk_exams_school_status_idx');
            $table->index(['study_group_id', 'semester'], 'ljk_exams_group_semester_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_exams');
    }
};
