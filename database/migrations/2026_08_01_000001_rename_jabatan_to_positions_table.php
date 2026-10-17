<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename table jabatan -> positions
        Schema::rename('jabatan', 'positions');

        if (DB::getDriverName() === 'mysql') {
            // MySQL: drop old foreign key and add new one with updated table name
            $foreigns = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'gtk_employments'
                AND CONSTRAINT_NAME LIKE '%jabatan%'
            ");

            foreach ($foreigns as $fk) {
                DB::statement("ALTER TABLE `gtk_employments` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            DB::statement('
                ALTER TABLE `gtk_employments`
                ADD CONSTRAINT `gtk_employments_jabatan_id_foreign`
                FOREIGN KEY (`jabatan_id`) REFERENCES `positions`(`id`) ON DELETE SET NULL
            ');
        } else {
            // SQLite: rebuild foreign key using schema builder
            Schema::table('gtk_employments', function (Blueprint $table) {
                $table->dropForeign(['jabatan_id']);
            });
            Schema::table('gtk_employments', function (Blueprint $table) {
                $table->foreign('jabatan_id')
                    ->references('id')
                    ->on('positions')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Remove the foreign key we added
            DB::statement('
                ALTER TABLE `gtk_employments`
                DROP FOREIGN KEY `gtk_employments_jabatan_id_foreign`
            ');

            // Add back old foreign key to jabatan table
            DB::statement('
                ALTER TABLE `gtk_employments`
                ADD CONSTRAINT `gtk_employments_jabatan_id_foreign`
                FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan`(`id`) ON DELETE SET NULL
            ');
        } else {
            Schema::table('gtk_employments', function (Blueprint $table) {
                $table->dropForeign(['jabatan_id']);
            });
            Schema::table('gtk_employments', function (Blueprint $table) {
                $table->foreign('jabatan_id')
                    ->references('id')
                    ->on('jabatan')
                    ->onDelete('set null');
            });
        }

        // Rename table back
        Schema::rename('positions', 'jabatan');
    }
};
