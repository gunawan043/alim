<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penghuni asrama dapat AKTIF tanpa kamar (hasil "keluarkan dari kamar"
 * atau belum ditempatkan) — room/bed menjadi nullable agar penempatan ulang
 * lewat alur "tarik data" bekerja.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('dormitory_residents', function (Blueprint $table) {
                $table->uuid('room_id')->nullable()->change();
                $table->unsignedInteger('bed_number')->nullable()->change();
            });
        } catch (\Throwable) {
            // Kolom sudah nullable pada sebagian driver.
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan NOT NULL (data historis tetap valid).
    }
};
