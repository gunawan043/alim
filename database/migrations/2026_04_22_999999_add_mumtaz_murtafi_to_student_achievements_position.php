<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: SQLite does not support MODIFY COLUMN
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE student_achievements MODIFY COLUMN `position` ENUM(
            'juara_1', 'juara_2', 'juara_3',
            'harapan_1', 'harapan_2', 'harapan_3',
            'peserta', 'mumtaz_murtafi', 'lainnya'
        )");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE student_achievements MODIFY COLUMN `position` ENUM(
            'juara_1', 'juara_2', 'juara_3',
            'harapan_1', 'harapan_2', 'harapan_3',
            'peserta', 'lainnya'
        )");
    }
};
