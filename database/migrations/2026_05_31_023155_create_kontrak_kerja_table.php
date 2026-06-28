<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kontrak_kerja', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gtk_uuid')->constrained('gtk_profiles')->cascadeOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('nomor_kontrak')->unique();
            $table->enum('jenis', ['pkwt', 'pkwt_perpanjangan', 'perjanjian_kerja_harian', 'magang', 'mitra']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedTinyInteger('durasi_bulan')->nullable();
            $table->string('jabatan');
            $table->string('unit_kerja')->nullable();
            $table->text('ruanglingkup')->nullable();
            $table->text('ketentuan_pkwt')->nullable();
            $table->string('lokasi_kerja')->nullable();
            $table->decimal('gaji_pokok', 14, 2)->nullable();
            $table->decimal('tunjangan_tetap', 14, 2)->default(0);
            $table->decimal('tunjangan_tidak_tetap', 14, 2)->default(0);
            $table->enum('status', ['draft', 'aktif', 'perpanjangan', 'selesai', 'diputus'])->default('draft');
            $table->date('tanggal_berakhir')->nullable();
            $table->text('dokumen_path')->nullable();
            $table->string('nama_penanda_tangan')->nullable();
            $table->string('jabatan_penanda_tangan')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index('gtk_uuid');
            $table->index('school_id');
            $table->index('status');
            $table->index('tanggal_selesai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_kerja');
    }
};
