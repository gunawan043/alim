<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('soal')) {
            return;
        }

        Schema::table('soal', function (Blueprint $table) {
            if (! Schema::hasColumn('soal', 'level_kognitif')) {
                $table->string('level_kognitif', 30)->nullable()->after('tingkat_kesulitan_estimasi');
            }

            if (! Schema::hasColumn('soal', 'level_kognitif_rumpun')) {
                $table->string('level_kognitif_rumpun', 5)->nullable()->after('level_kognitif');
            }

            if (! Schema::hasColumn('soal', 'bloom_confidence')) {
                $table->unsignedTinyInteger('bloom_confidence')->nullable()->after('level_kognitif_rumpun');
            }

            if (! Schema::hasColumn('soal', 'bloom_classified_at')) {
                $table->timestamp('bloom_classified_at')->nullable()->after('bloom_confidence');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('soal')) {
            return;
        }

        Schema::table('soal', function (Blueprint $table) {
            foreach (['bloom_classified_at', 'bloom_confidence', 'level_kognitif_rumpun', 'level_kognitif'] as $column) {
                if (Schema::hasColumn('soal', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
