<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('recruitment_profile_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('recruitment_job_id')
                ->constrained()
                ->cascadeOnDelete();

            // ❌ HAPUS current_stage_id DARI SINI

            $table->string('no_lamaran')->unique();

            $table->enum('status', [
                'draft',
                'menunggu_seleksi',
                'seleksi_administrasi',
                'lolos_administrasi',
                'tidak_lolos_administrasi',
                'tes_tertulis',
                'lolos_tes_tertulis',
                'tidak_lolos_tes_tertulis',
                'tes_praktek',
                'lolos_tes_praktek',
                'tidak_lolos_tes_praktek',
                'wawancara_hr',
                'lolos_wawancara_hr',
                'tidak_lolos_wawancara_hr',
                'wawancara_user',
                'lolos_wawancara_user',
                'tidak_lolos_wawancara_user',
                'medical_checkup',
                'lolos_mcu',
                'tidak_lolos_mcu',
                'psychological_test',
                'lolos_psikotes',
                'tidak_lolos_psikotes',
                'penawaran_kerja',
                'diterima',
                'ditolak',
                'mengundurkan_diri',
                'blacklist',
            ])->default('draft');

            $table->integer('skor_administrasi')->nullable();
            $table->decimal('nilai_tes', 5, 2)->nullable();
            $table->decimal('nilai_wawancara', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->integer('ranking')->nullable();

            $table->date('tanggal_melamar');
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('selesai_at')->nullable();

            $table->text('catatan_pelamar')->nullable();
            $table->text('catatan_rekruter')->nullable();
            $table->json('feedback')->nullable();

            $table->foreignUuid('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['recruitment_profile_id', 'recruitment_job_id'],
                'unique_application'
            );

            $table->index('status');
            $table->index('tanggal_melamar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_applications');
    }
};
