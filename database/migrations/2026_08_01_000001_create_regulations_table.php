<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel peraturan asrama (BAB IV dan BAB V).
     *
     * Menyimpan isi peraturan asrama secara terstruktur agar dapat diubah
     * melalui admin panel tanpa harus memodifikasi kode.
     *
     * Struktur:
     *   - id: UUID primary key
     *   - bab: Nomor bab (IV, V, dll)
     *   - pasal: Nomor pasal dalam bab (10, 11, dst)
     *   - title: Judul pasal (Pengertian Perizinan, Tujuan Perizinan, dsb)
     *   - content: Isi lengkap pasal (dapat berupa HTML atau teks biasa)
     *   - order: Urutan tampil dalam bab
     *   - created_at, updated_at
     */
    public function up(): void
    {
        Schema::create('regulations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('bab', 10)->nullable()->comment('Nomor bab misal: IV, V');
            $table->string('pasal', 10)->nullable()->comment('Nomor pasal dalam bab');
            $table->string('title', 255)->notNull()->comment('Judul pasal');
            $table->text('content')->notNull()->comment('Isi lengkap pasal');
            $table->integer('order')->default(0)->comment('Urutan tampil dalam bab');
            $table->timestamps();

            $table->index('bab');
            $table->index('pasal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulations');
    }
};
