<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Manual Adjustment PROSEM:
 *  - prosem_items.sumber  : otomatis (generator) / manual (disesuaikan guru)
 *  - prosem.adjusted_at   : jejak kapan terakhir disesuaikan manual
 *  - prosem_item_weeks    : distribusi rinci per pekan (JP per pekan)
 *
 * Hanya mengubah DISTRIBUSI WAKTU — tidak mengubah total alokasi TP,
 * tidak membuat kalender/tabel pekan baru, dan tidak menyentuh
 * referensi jurnal (prosem_item_id) sehingga realisasi tetap utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('prosem_items', 'sumber')) {
            Schema::table('prosem_items', function (Blueprint $table) {
                $table->enum('sumber', ['otomatis', 'manual'])->default('otomatis')->after('jp');
            });
        }

        if (! Schema::hasColumn('prosem', 'adjusted_at')) {
            Schema::table('prosem', function (Blueprint $table) {
                $table->timestamp('adjusted_at')->nullable()->after('synced_at');
            });
        }

        if (! Schema::hasTable('prosem_item_weeks')) {
            Schema::create('prosem_item_weeks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('prosem_item_id');
                $table->unsignedInteger('pekan_ke');
                $table->unsignedInteger('jp')->default(0);
                $table->timestamps();

                $table->foreign('prosem_item_id', 'prosem_weeks_item_fk')
                    ->references('id')->on('prosem_items')->cascadeOnDelete();
                $table->unique(['prosem_item_id', 'pekan_ke'], 'prosem_weeks_unique');
            });
        }

        // Backfill distribusi item lama (sebelum adjustment tersedia):
        // sebar JP merata pada rentang pekan yang sudah ada.
        if (Schema::hasTable('prosem_item_weeks') && DB::table('prosem_item_weeks')->count() === 0) {
            $items = DB::table('prosem_items')
                ->where('mulai_minggu_ke', '>', 0)
                ->whereColumn('selesai_minggu_ke', '>=', 'mulai_minggu_ke')
                ->get();

            foreach ($items as $item) {
                $weeks = range((int) $item->mulai_minggu_ke, (int) $item->selesai_minggu_ke);
                $count = count($weeks);
                $jp = (int) $item->jp;

                if ($count === 0 || $jp === 0) {
                    continue;
                }

                $perWeek = intdiv($jp, $count);
                $remainder = $jp % $count;

                foreach ($weeks as $index => $pekanKe) {
                    $alloc = $perWeek + ($index === $count - 1 ? $remainder : 0);
                    if ($alloc <= 0) {
                        continue;
                    }

                    DB::table('prosem_item_weeks')->insert([
                        'id' => (string) Str::uuid(),
                        'prosem_item_id' => $item->id,
                        'pekan_ke' => $pekanKe,
                        'jp' => $alloc,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prosem_item_weeks');

        if (Schema::hasColumn('prosem', 'adjusted_at')) {
            Schema::table('prosem', function (Blueprint $table) {
                $table->dropColumn('adjusted_at');
            });
        }

        if (Schema::hasColumn('prosem_items', 'sumber')) {
            Schema::table('prosem_items', function (Blueprint $table) {
                $table->dropColumn('sumber');
            });
        }
    }
};
