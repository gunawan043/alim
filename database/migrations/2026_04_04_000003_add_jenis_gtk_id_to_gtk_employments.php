<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If positions table doesn't exist yet (migration ordering issue),
        // create it from jabatan or skip foreign key setup.
        $positionsExists = Schema::hasTable('positions');
        $jabatanExists = Schema::hasTable('jabatan');

        Schema::table('gtk_employments', function (Blueprint $table) {
            $table->uuid('jenis_gtk_id')->nullable()->after('jenis_gtk');
            $table->uuid('jabatan_id')->nullable()->after('jabatan');
        });

        if (Schema::hasTable('jenis_gtk')) {
            Schema::table('gtk_employments', function (Blueprint $table) {
                $table->foreign('jenis_gtk_id')
                    ->references('id')
                    ->on('jenis_gtk')
                    ->onDelete('set null');
            });

            // Sync existing string values → UUIDs in jenis_gtk_id
            $mapping = DB::table('jenis_gtk')->pluck('id', 'nama');
            foreach ($mapping as $name => $uuid) {
                DB::table('gtk_employments')
                    ->where('jenis_gtk', $name)
                    ->update(['jenis_gtk_id' => $uuid]);
            }
        }

        // Determine the source table for jabatan → positions mapping
        // At this migration point, table is still 'jabatan'; 'positions' is created by later rename migration
        if (Schema::hasTable('positions')) {
            $sourceTable = 'positions';
        } elseif (Schema::hasTable('jabatan')) {
            $sourceTable = 'jabatan';
        } else {
            $sourceTable = null;
        }

        if ($sourceTable && Schema::hasTable('gtk_employments') && Schema::hasColumn('gtk_employments', 'jabatan_id')) {
            Schema::table('gtk_employments', function (Blueprint $table) use ($sourceTable) {
                $table->foreign('jabatan_id')
                    ->references('id')
                    ->on($sourceTable)
                    ->onDelete('set null');
            });

            // Sync existing string values → UUIDs in jabatan_id
            $jabMapping = DB::table($sourceTable)->pluck('id', 'nama');
            foreach ($jabMapping as $name => $uuid) {
                DB::table('gtk_employments')
                    ->where('jabatan', $name)
                    ->update(['jabatan_id' => $uuid]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('gtk_employments', function (Blueprint $table) {
            $table->dropForeign(['jenis_gtk_id']);
            $table->dropForeign(['jabatan_id']);
            $table->dropColumn(['jenis_gtk_id', 'jabatan_id']);
        });
    }
};
