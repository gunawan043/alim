<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supervisi ↔ instrumen: kolom instrumen + hasil penilaian per item.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supervisi')) {
            Schema::table('supervisi', function (Blueprint $table) {
                if (! Schema::hasColumn('supervisi', 'instrumen_id')) {
                    $table->uuid('instrumen_id')->nullable()->after('jenis_supervisi');
                }
                if (! Schema::hasColumn('supervisi', 'skor_total')) {
                    $table->decimal('skor_total', 8, 2)->nullable();
                }
                if (! Schema::hasColumn('supervisi', 'skor_maks')) {
                    $table->decimal('skor_maks', 8, 2)->nullable();
                }
                if (! Schema::hasColumn('supervisi', 'nilai_akhir')) {
                    $table->decimal('nilai_akhir', 5, 2)->nullable();
                }
                if (! Schema::hasColumn('supervisi', 'ketercapaian')) {
                    $table->string('ketercapaian', 30)->nullable();
                }
            });
        }

        if (! Schema::hasTable('supervisi_penilaian')) {
            Schema::create('supervisi_penilaian', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('supervisi_id');
                $table->uuid('item_id');
                $table->decimal('skor', 5, 2)->nullable();   // 4/3/2/1 (A/B/C/D)
                $table->boolean('kondisi')->nullable();      // Ya / Tidak
                $table->string('catatan', 500)->nullable();
                $table->timestamps();

                $table->unique(['supervisi_id', 'item_id'], 'supervisi_penilaian_unique');
                $table->foreign('supervisi_id')->references('id')->on('supervisi')->cascadeOnDelete();
                $table->foreign('item_id')->references('id')->on('supervisi_instrumen_item')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisi_penilaian');

        if (Schema::hasTable('supervisi')) {
            Schema::table('supervisi', function (Blueprint $table) {
                foreach (['instrumen_id', 'skor_total', 'skor_maks', 'nilai_akhir', 'ketercapaian'] as $col) {
                    if (Schema::hasColumn('supervisi', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
