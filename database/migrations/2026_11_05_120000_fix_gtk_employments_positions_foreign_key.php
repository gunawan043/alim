<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite menyimpan definisi foreign key di DDL tabel, jadi menghapus tabel
     * `positions` tidak menghapus FK gtk_employments.jabatan_id yang menunjuk ke
     * sana. Akibatnya setiap insert dengan jabatan_id gagal dengan
     * "no such table: main.positions".
     *
     * Migrasi merge sebelumnya hanya menangani MySQL, jadi perbaikan ini
     * membangun ulang tabel di SQLite tanpa FK yang sudah yatim tersebut.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // Sisa percobaan yang gagal di tengah jalan: tabel sementara sudah ada
        // tetapi tabel asli belum diganti.
        if (! Schema::hasTable('gtk_employments') && Schema::hasTable('gtk_employments__rebuild')) {
            DB::statement('ALTER TABLE gtk_employments__rebuild RENAME TO gtk_employments');

            return;
        }

        $foreignKeys = DB::select('PRAGMA foreign_key_list(gtk_employments)');
        $hasStaleForeignKey = collect($foreignKeys)->contains(
            fn ($fk) => $fk->from === 'jabatan_id' && $fk->table === 'positions'
        );

        if (! $hasStaleForeignKey) {
            return;
        }

        $createSql = DB::selectOne(
            "select sql from sqlite_master where type = 'table' and name = 'gtk_employments'"
        )?->sql;

        if (! $createSql) {
            return;
        }

        $patchedSql = preg_replace(
            '/,\s*foreign key\("jabatan_id"\) references "positions"\("id"\) on delete set null/',
            '',
            $createSql,
            1,
            $removed
        );

        if (! $removed) {
            Log::warning('FK jabatan_id -> positions tidak ditemukan pada DDL gtk_employments; migrasi dilewati.');

            return;
        }

        $patchedSql = preg_replace(
            '/^CREATE TABLE "gtk_employments"/',
            'CREATE TABLE "gtk_employments__rebuild"',
            $patchedSql,
            1
        );

        $columns = collect(DB::select('PRAGMA table_info(gtk_employments)'))
            ->pluck('name')
            ->implode(', ');

        DB::statement('DROP TABLE IF EXISTS gtk_employments__rebuild');

        // gtk_position_proposals.current_employment_id menunjuk ke tabel ini,
        // jadi FK harus dimatikan saat tabel lama di-drop.
        Schema::disableForeignKeyConstraints();

        try {
            DB::statement($patchedSql);
            DB::statement("INSERT INTO gtk_employments__rebuild ({$columns}) SELECT {$columns} FROM gtk_employments");
            DB::statement('DROP TABLE gtk_employments');
            DB::statement('ALTER TABLE gtk_employments__rebuild RENAME TO gtk_employments');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS gtk_employments_nupy_unique ON gtk_employments (nupy)');
        DB::statement('CREATE INDEX IF NOT EXISTS gtk_employments_school_id_index ON gtk_employments (school_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS gtk_employments_academic_year_id_index ON gtk_employments (academic_year_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS gtk_employments_is_homeroom_index ON gtk_employments (is_homeroom)');
    }

    public function down(): void
    {
        // Tidak dikembalikan: FK lama menunjuk tabel yang sudah dihapus.
    }
};
