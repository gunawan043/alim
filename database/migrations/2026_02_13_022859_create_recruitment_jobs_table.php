<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_unit_id')
                ->nullable()
                ->constrained('work_units')
                ->nullOnDelete();

            // Informasi Lowongan
            $table->string('kode_lowongan')->unique();
            $table->string('judul');
            $table->string('posisi');
            $table->enum('jenis_pegawai', ['pns', 'pppk', 'honor', 'kontrak', 'magang'])->nullable();
            $table->enum('status_pegawai', ['tetap', 'kontrak', 'probation'])->nullable();

            // Kualifikasi
            $table->json('persyaratan_umum')->nullable();
            $table->json('persyaratan_khusus')->nullable();
            $table->json('kualifikasi_pendidikan')->nullable(); // Minimal pendidikan
            $table->json('kualifikasi_pengalaman')->nullable(); // Minimal pengalaman
            $table->json('kompetensi_dibutuhkan')->nullable(); // Skill yang dibutuhkan

            // Detail
            $table->integer('kuota');
            $table->integer('kuota_terisi')->default(0);
            $table->text('deskripsi_pekerjaan')->nullable();
            $table->text('fasilitas')->nullable();
            $table->json('rentang_gaji')->nullable();

            // Timeline
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['draft', 'aktif', 'ditutup', 'dibatalkan'])->default('draft');

            // Tahapan
            $table->json('tahapan_seleksi')->nullable(); // Array of stages

            // Approval
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('kode_lowongan');
            $table->index('posisi');
            $table->index('status');
            $table->index('tanggal_mulai');
            $table->index('tanggal_selesai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_jobs');
    }
};
