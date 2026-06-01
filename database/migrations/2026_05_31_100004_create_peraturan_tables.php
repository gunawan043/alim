<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kategori dokumen peraturan
        Schema::create('peraturan_kategori', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama'); // SOP, SK, Peraturan Yayasan, Kebijakan
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Dokumen peraturan
        Schema::create('peraturan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peraturan_kategori_id')->nullable()->constrained('peraturan_kategori')->nullOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('nomor_dokumen')->nullable();
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->string('dokumen_path')->nullable();
            $table->string('versi')->default('1.0');
            $table->enum('status', ['aktif', 'nonaktif', 'draft', 'revisi'])->default('aktif');
            $table->text('catatan_perubahan')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignUuid('ditandatangani_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index('peraturan_kategori_id');
            $table->index('status');
        });

        // Pelanggaran GTK
        Schema::create('pelanggaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama'); // Terlambat, Tidak Hadir, Melanggar SOP
            $table->enum('jenis', ['ringan', 'sedang', 'berat']);
            $table->integer('poin')->default(0);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Log pelanggaran GTK
        Schema::create('pelanggaran_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pelanggaran_id')->constrained('pelanggaran')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->string('dokumen_path')->nullable();
            $table->foreignUuid('dicatat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index('user_id');
            $table->index('tanggal');
        });

        // Log pembacaan dokumen (user harus baca& ack)
        Schema::create('peraturan_read_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peraturan_id')->constrained('peraturan')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['peraturan_id', 'user_id'], 'read_log_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peraturan_read_log');
        Schema::dropIfExists('pelanggaran_log');
        Schema::dropIfExists('pelanggaran');
        Schema::dropIfExists('peraturan');
        Schema::dropIfExists('peraturan_kategori');
    }
};
