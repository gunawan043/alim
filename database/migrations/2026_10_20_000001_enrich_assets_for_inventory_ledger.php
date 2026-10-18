<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('asset_name')
                    ->comment('Jumlah unit pada baris inventaris');
            }
            if (! Schema::hasColumn('assets', 'unit')) {
                $table->string('unit', 30)->default('unit')->after('quantity')
                    ->comment('Satuan: unit, buah, set, lembar, pasang');
            }
            if (! Schema::hasColumn('assets', 'useful_life_years')) {
                $table->unsignedSmallInteger('useful_life_years')->nullable()->after('depreciation_per_year')
                    ->comment('Masa pakai aset dalam tahun');
            }
            if (! Schema::hasColumn('assets', 'work_unit_sequence')) {
                $table->unsignedInteger('work_unit_sequence')->nullable()->after('asset_code')
                    ->comment('Nomor urut inventaris per satuan kerja');
            }
        });

        // Backfill nomor urut satker agar Buku Induk dapat dicetak konsisten.
        $assets = DB::table('assets')
            ->select('id', 'work_unit_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('work_unit_id');

        foreach ($assets as $rows) {
            $sequence = 1;
            foreach ($rows as $row) {
                DB::table('assets')
                    ->where('id', $row->id)
                    ->update(['work_unit_sequence' => $sequence++]);
            }
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->index(['work_unit_id', 'work_unit_sequence'], 'assets_work_unit_sequence_index');
            $table->index(['room_id', 'asset_category_id'], 'assets_room_category_index');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_work_unit_sequence_index');
            $table->dropIndex('assets_room_category_index');
            $table->dropColumn(['quantity', 'unit', 'useful_life_years', 'work_unit_sequence']);
        });
    }
};
