<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tahfidz_setorans`
                MODIFY COLUMN `setoran_type`
                ENUM('ziyadah','murajaah','tikror','robet')
                NOT NULL
                COMMENT 'ziyadah=hafalan baru, tikror=pengulangan, murajaah=tasmi hafalan lama, robet=persiapan materi berikutnya'");
        } elseif (Schema::hasColumn('tahfidz_setorans', 'setoran_type')) {
            Schema::table('tahfidz_setorans', function (Blueprint $table) {
                $table->enum('setoran_type', ['ziyadah', 'murajaah', 'tikror', 'robet'])
                    ->comment('ziyadah=hafalan baru, tikror=pengulangan, murajaah=tasmi hafalan lama, robet=persiapan materi berikutnya')
                    ->change();
            });
        }

        $columns = [
            'tikrar_task' => fn (Blueprint $table) => $table->text('tikrar_task')->nullable()->after('keterangan_capaian'),
            'nilai_tikrar' => fn (Blueprint $table) => $table->decimal('nilai_tikrar', 5, 2)->nullable()->after('tikrar_task'),
            'hasil_hafalan_catatan' => fn (Blueprint $table) => $table->text('hasil_hafalan_catatan')->nullable()->after('nilai_tikrar'),
            'lahn_khofi_count' => fn (Blueprint $table) => $table->unsignedSmallInteger('lahn_khofi_count')->default(0)->after('hasil_hafalan_catatan'),
            'lahn_jali_count' => fn (Blueprint $table) => $table->unsignedSmallInteger('lahn_jali_count')->default(0)->after('lahn_khofi_count'),
            'nilai_lahn' => fn (Blueprint $table) => $table->decimal('nilai_lahn', 5, 2)->nullable()->after('lahn_jali_count'),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('tahfidz_setorans', $name)) {
                Schema::table('tahfidz_setorans', $definition);
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'nilai_lahn',
            'lahn_jali_count',
            'lahn_khofi_count',
            'hasil_hafalan_catatan',
            'nilai_tikrar',
            'tikrar_task',
        ] as $column) {
            if (Schema::hasColumn('tahfidz_setorans', $column)) {
                Schema::table('tahfidz_setorans', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tahfidz_setorans`
                MODIFY COLUMN `setoran_type`
                ENUM('ziyadah','murajaah','tikror')
                NOT NULL
                COMMENT 'ziyadah=hafalan baru, murajaah=pengulangan, tikror=penugasan ulang'");
        } elseif (Schema::hasColumn('tahfidz_setorans', 'setoran_type')) {
            Schema::table('tahfidz_setorans', function (Blueprint $table) {
                $table->enum('setoran_type', ['ziyadah', 'murajaah', 'tikror'])
                    ->comment('ziyadah=hafalan baru, murajaah=pengulangan, tikror=penugasan ulang')
                    ->change();
            });
        }
    }
};
