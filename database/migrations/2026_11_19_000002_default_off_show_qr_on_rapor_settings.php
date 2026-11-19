<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rapor default memakai tanda tangan visual (bukan QR). Baris yang sudah
 * terlanjur memakai default lama (QR aktif) diubah agar QR tidak selalu
 * tercetak; admin dapat mengaktifkannya lagi lewat switch "Tampilkan QR".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')
            || ! Schema::hasColumn('rapor_distribution_settings', 'show_qr')) {
            return;
        }

        DB::table('rapor_distribution_settings')->update(['show_qr' => false]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: nilai show_qr adalah preferensi admin.
    }
};
