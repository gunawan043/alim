<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blok asrama kini merujuk ke Gedung Sarpras (asset_buildings) yang dikelola URT,
 * bukan tabel sarpras_buildings yang hanya diisi dari modul asrama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_wings', function (Blueprint $table) {
            $table->foreignUuid('asset_building_id')->nullable()->after('sarpras_building_id')
                ->constrained('asset_buildings')->nullOnDelete();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('dormitory_wings', function (Blueprint $table) {
            $table->dropForeign(['asset_building_id']);
            $table->dropColumn('asset_building_id');
        });
    }

    /**
     * Isi asset_building_id untuk blok lama:
     * 1) lewat kode/nama pada sarpras_buildings, 2) lewat awalan nama blok.
     */
    private function backfill(): void
    {
        $buildings = DB::table('asset_buildings')
            ->select('id', 'building_code', 'building_name')
            ->get();

        if ($buildings->isEmpty()) {
            return;
        }

        $byCode = $buildings->keyBy(fn ($b) => strtoupper((string) $b->building_code));
        $byName = $buildings->keyBy(fn ($b) => mb_strtolower(trim((string) $b->building_name)));

        $wings = DB::table('dormitory_wings')->select('id', 'name', 'sarpras_building_id')->get();

        foreach ($wings as $wing) {
            $match = null;

            if ($wing->sarpras_building_id) {
                $legacy = DB::table('sarpras_buildings')->where('id', $wing->sarpras_building_id)->first();
                if ($legacy) {
                    $match = $byCode[strtoupper((string) $legacy->code)] ?? $byName[mb_strtolower(trim((string) $legacy->name))] ?? null;
                }
            }

            if (! $match) {
                $base = mb_strtolower(trim(explode(' — ', (string) $wing->name)[0]));
                $match = $byName[$base] ?? null;
            }

            if ($match) {
                DB::table('dormitory_wings')->where('id', $wing->id)->update(['asset_building_id' => $match->id]);
            }
        }
    }
};
