<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE student_achievements MODIFY COLUMN `position` ENUM(
            'juara_1', 'juara_2', 'juara_3',
            'harapan_1', 'harapan_2', 'harapan_3',
            'peserta', 'mumtaz_murtafi', 'lainnya'
        )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE student_achievements MODIFY COLUMN `position` ENUM(
            'juara_1', 'juara_2', 'juara_3',
            'harapan_1', 'harapan_2', 'harapan_3',
            'peserta', 'lainnya'
        )");
    }
};
