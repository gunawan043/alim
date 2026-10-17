<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_trainings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recruitment_profile_id')->constrained()->cascadeOnDelete();

            // Jenis Training
            $table->enum('jenis', [
                'pelatihan', 'workshop', 'seminar', 'kursus',
                'sertifikasi_profesi', 'diklat', 'bootcamp', 'lainnya',
            ]);

            $table->string('nama_pelatihan');
            $table->string('penyelenggara');
            $table->enum('tingkat', ['lokal', 'nasional', 'internasional'])->nullable();

            // Periode
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->integer('durasi_jam')->nullable(); // Total jam pelatihan

            // Sertifikasi
            $table->boolean('memiliki_sertifikat')->default(true);
            $table->string('no_sertifikat')->nullable();
            $table->date('tanggal_sertifikat')->nullable();
            $table->date('masa_berlaku')->nullable();
            $table->enum('status_sertifikat', ['aktif', 'kadaluarsa', 'dalam_proses'])->nullable();

            // Detail
            $table->text('deskripsi_materi')->nullable();
            $table->json('kompetensi_diperoleh')->nullable();
            $table->string('nilai')->nullable();

            // File
            $table->string('sertifikat_path')->nullable();
            $table->string('materi_path')->nullable();

            // Verifikasi
            $table->boolean('is_verified')->default(false);
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('jenis');
            $table->index('nama_pelatihan');
            $table->index('penyelenggara');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_trainings');
    }
};
