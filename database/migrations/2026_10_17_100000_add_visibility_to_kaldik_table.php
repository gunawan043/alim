<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agenda Kaldik: privasi berjenjang.
 * - visibility=satuan  → agenda satuan kerja (terlihat GTK dalam satuan tsb)
 * - visibility=pribadi → agenda pribadi (HANYA pemiliknya)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kaldik')) {
            return;
        }

        Schema::table('kaldik', function (Blueprint $table) {
            if (! Schema::hasColumn('kaldik', 'visibility')) {
                $table->enum('visibility', ['satuan', 'pribadi'])->default('satuan')->after('category');
            }
        });

        if (! Schema::hasColumn('kaldik', 'owner_user_id')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->uuid('owner_user_id')->nullable()->after('work_unit_id');
                $table->index('owner_user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kaldik')) {
            return;
        }

        Schema::table('kaldik', function (Blueprint $table) {
            if (Schema::hasColumn('kaldik', 'visibility')) {
                $table->dropColumn('visibility');
            }
            if (Schema::hasColumn('kaldik', 'owner_user_id')) {
                $table->dropIndex(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            }
        });
    }
};
