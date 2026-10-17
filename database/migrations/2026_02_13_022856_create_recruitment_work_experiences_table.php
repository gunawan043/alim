<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_work_experiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recruitment_profile_id')->constrained()->cascadeOnDelete();

            // Data Perusahaan
            $table->string('nama_perusahaan');
            $table->enum('jenis_perusahaan', [
                'pemerintah', 'swasta', 'bumn', 'bumd',
                'nirlaba', 'pendidikan', 'lainnya',
            ])->nullable();
            $table->string('bidang_perusahaan')->nullable();

            // Posisi & Status
            $table->string('posisi_terakhir');
            $table->enum('status_kepegawaian', [
                'tetap', 'kontrak', 'magang', 'harian_lepas',
                'outsourcing', 'konsultan', 'lainnya',
            ])->nullable();

            // Periode
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_saat_ini')->default(false);
            $table->integer('lama_bekerja_bulan')->nullable(); // Auto calculate

            // Detail Pekerjaan
            $table->text('jobdesc')->nullable();
            $table->json('kompetensi_utama')->nullable(); // Skill yang digunakan
            $table->json('pencapaian')->nullable(); // Achievement/penghargaan

            // Gaji
            $table->decimal('gaji_terakhir', 15, 2)->nullable();
            $table->enum('gaji_periode', ['bulan', 'tahun'])->nullable();

            // Atasan
            $table->string('nama_atasan')->nullable();
            $table->string('kontak_atasan')->nullable();
            $table->string('email_atasan')->nullable();

            // File Pendukung
            $table->string('sertifikat_kerja_path')->nullable();
            $table->string('referensi_path')->nullable();
            $table->string('paklaring_path')->nullable();

            // Alasan Keluar
            $table->enum('alasan_keluar', [
                'mengundurkan_diri', 'kontrak_selesai', 'phk',
                'pensiun', 'pengembangan_karir', 'lainnya',
            ])->nullable();
            $table->text('alasan_keluar_lainnya')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('posisi_terakhir');
            $table->index('nama_perusahaan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_work_experiences');
    }
};
