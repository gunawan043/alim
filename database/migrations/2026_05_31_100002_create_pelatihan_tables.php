<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jenis/metadata pelatihan
        Schema::create('jenis_pelatihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Master pelatihan
        Schema::create('pelatihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->foreignUuid('jenis_pelatihan_id')->nullable()->constrained('jenis_pelatihan')->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->string('vendor')->nullable(); // institusi pelaksana
            $table->string('lokasi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('jumlah_jam')->nullable();     // durasi jam pelatihan
            $table->enum('metode', ['offline', 'online', 'hybrid'])->default('offline');
            $table->string('penggunaan_metode')->nullable();
            $table->decimal('biaya_per_peserta', 14, 2)->nullable();
            $table->text('materi_path')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status', ['rencana', 'berjalan', 'selesai', 'batal'])->default('rencana');
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('tanggal_mulai');
        });

        // Peserta pelatihan
        Schema::create('pelatihan_peserta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pelatihan_id')->constrained('pelatihan')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status_kehadiran', ['daftar', 'hadir', 'tidak_hadir', 'ijin'])->default('daftar');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['pelatihan_id', 'user_id'], 'peserta_unique');
            $table->index('pelatihan_id');
            $table->index('user_id');
        });

        // Sertifikat pelatihan
        Schema::create('pelatihan_sertifikat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pelatihan_peserta_id')->constrained('pelatihan_peserta')->cascadeOnDelete();
            $table->string('nomor_sertifikat')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->string('dokumen_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Evaluasi pasca pelatihan
        Schema::create('pelatihan_evaluasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pelatihan_id')->constrained('pelatihan')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('skor_pelatihan')->nullable();  // 1-5
            $table->text('feedback')->nullable();
            $table->boolean('dokumentasi_uploaded')->default(false);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('pelatihan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelatihan_evaluasi');
        Schema::dropIfExists('pelatihan_sertifikat');
        Schema::dropIfExists('pelatihan_peserta');
        Schema::dropIfExists('pelatihan');
        Schema::dropIfExists('jenis_pelatihan');
    }
};
