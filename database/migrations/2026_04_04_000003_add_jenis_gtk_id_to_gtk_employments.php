<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtk_employments', function (Blueprint $table) {
            $table->uuid('jenis_gtk_id')->nullable()->after('jenis_gtk');
            $table->uuid('jabatan_id')->nullable()->after('jabatan');

            $table->foreign('jenis_gtk_id')
                ->references('id')
                ->on('jenis_gtk')
                ->onDelete('set null');

            $table->foreign('jabatan_id')
                ->references('id')
                ->on('jabatan')
                ->onDelete('set null');
        });

        // Sync existing string values → UUIDs in jenis_gtk_id
        $mapping = DB::table('jenis_gtk')->pluck('id', 'nama');
        foreach ($mapping as $name => $uuid) {
            DB::table('gtk_employments')
                ->where('jenis_gtk', $name)
                ->update(['jenis_gtk_id' => $uuid]);
        }

        // Sync existing string values → UUIDs in jabatan_id
        $jabMapping = DB::table('jabatan')->pluck('id', 'nama');
        foreach ($jabMapping as $name => $uuid) {
            DB::table('gtk_employments')
                ->where('jabatan', $name)
                ->update(['jabatan_id' => $uuid]);
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
