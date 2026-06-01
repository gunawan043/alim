<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master bantuan/kesejahteraan
        Schema::create('kesejahteraan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->enum('jenis', ['bantuan', 'santunan', 'bpjs', 'klaim', 'fasilitas']);
            $table->text('deskripsi')->nullable();
            $table->decimal('nilai_default', 14, 2)->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Penerima bantuan
        Schema::create('kesejahteraan_penerima', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kesejahteraan_id')->constrained('kesejahteraan')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('nilai', 14, 2)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['aktif', 'nonaktif', 'pending'])->default('pending');
            $table->text('catatan')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['kesejahteraan_id', 'user_id'], 'penerima_unique');
            $table->index('user_id');
            $table->index('status');
        });

        // Klaim kesejahteraan
        Schema::create('kesejahteraan_klaim', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('kesejahteraan_id')->constrained('kesejahteraan')->cascadeOnDelete();
            $table->string('nomor_klaim')->unique();
            $table->decimal('nilai_diminta', 14, 2);
            $table->decimal('nilai_disetujui', 14, 2)->nullable();
            $table->text('deskripsi_kejadian')->nullable();
            $table->string('dokumen_path')->nullable();
            $table->enum('status', ['pengajuan', 'diproses', 'disetujui', 'ditolak', 'dibayar'])->default('pengajuan');
            $table->text('catatan_admin')->nullable();
            $table->foreignUuid('diproses_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        // Data BPJS per GTK
        Schema::create('bpjs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_kartu')->nullable();
            $table->enum('jenis_bpjs', ['kesehatan', 'tenaga_kerja']);
            $table->date('tanggal_daftar')->nullable();
            $table->date('tanggal_nonaktif')->nullable();
            $table->decimal('iuran_per_bulan', 12, 2)->nullable();
            $table->decimal('iuran_perusahaan', 12, 2)->nullable();
            $table->decimal('iuran_pekerja', 12, 2)->nullable();
            $table->enum('status', ['aktif', 'nonaktif', 'pending'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'jenis_bpjs'], 'bpjs_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs');
        Schema::dropIfExists('kesejahteraan_klaim');
        Schema::dropIfExists('kesejahteraan_penerima');
        Schema::dropIfExists('kesejahteraan');
    }
};
