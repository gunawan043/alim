<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memulihkan tabel pivot `bank_soal_tp` pada instalasi SQLite.
 *
 * Migrasi aslinya (2026_06_19_020003) melewati SQLite, sehingga di server
 * yang memakai SQLite tabelnya tidak pernah dibuat padahal migrasi tercatat
 * "Ran". Akibatnya halaman detail bank soal gagal saat memuat relasi
 * tujuanPembelajaran.
 *
 * Bersifat idempoten: pada instalasi yang tabelnya sudah ada (MySQL) tidak
 * melakukan apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_soal_tp')) {
            return;
        }

        if (! Schema::hasTable('bank_soal') || ! Schema::hasTable('tujuan_pembelajaran')) {
            return;
        }

        Schema::create('bank_soal_tp', function (Blueprint $table) {
            $table->uuid('bank_soal_id');
            $table->uuid('tp_id');
            $table->timestamps();

            $table->primary(['bank_soal_id', 'tp_id']);
            $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->onDelete('cascade');
            $table->foreign('tp_id')->references('id')->on('tujuan_pembelajaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Tabel dipulihkan, bukan bagian dari perubahan skema aplikasi baru:
        // rollback tidak menghapusnya agar data pivot yang dipulihkan tetap ada.
    }
};
