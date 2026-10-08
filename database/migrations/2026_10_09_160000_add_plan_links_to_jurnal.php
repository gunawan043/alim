<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelaksanaan Pembelajaran: jurnal pertemuan terhubung ke rencana
 * (PROSEM → TP → RPM), sehingga realisasi TP/ATP dapat dihitung.
 *
 *  RPM/PROSEM → Jurnal → realisasi TP/ATP → asesmen → Buku Administrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_jurnal_pembelajaran', 'prosem_item_id')) {
            Schema::table('admin_jurnal_pembelajaran', function (Blueprint $table) {
                $table->uuid('prosem_item_id')->nullable()->after('admin_book_id');
                $table->uuid('perangkat_pembelajaran_id')->nullable()->after('prosem_item_id');
                $table->uuid('tujuan_pembelajaran_id')->nullable()->after('perangkat_pembelajaran_id');

                $table->index('prosem_item_id', 'jurnal_prosem_item_idx');
                $table->index('perangkat_pembelajaran_id', 'jurnal_perangkat_idx');
                $table->index('tujuan_pembelajaran_id', 'jurnal_tp_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_jurnal_pembelajaran', 'prosem_item_id')) {
            Schema::table('admin_jurnal_pembelajaran', function (Blueprint $table) {
                $table->dropIndex('jurnal_prosem_item_idx');
                $table->dropIndex('jurnal_perangkat_idx');
                $table->dropIndex('jurnal_tp_idx');
                $table->dropColumn(['prosem_item_id', 'perangkat_pembelajaran_id', 'tujuan_pembelajaran_id']);
            });
        }
    }
};
