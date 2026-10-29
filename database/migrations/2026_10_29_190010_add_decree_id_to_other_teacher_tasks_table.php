<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tugas tambahan perlu terikat ke SK tertentu agar mengedit satu SK tidak
     * menghapus tugas tambahan milik SK lain pada sekolah & tahun ajaran yang sama.
     */
    public function up(): void
    {
        if (Schema::hasColumn('other_teacher_tasks', 'decree_id')) {
            return;
        }

        Schema::table('other_teacher_tasks', function (Blueprint $table) {
            $table->foreignUuid('decree_id')
                ->nullable()
                ->after('academic_year_id')
                ->constrained('institution_decrees')
                ->nullOnDelete();

            $table->index(['decree_id', 'is_active'], 'ott_decree_active_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('other_teacher_tasks', 'decree_id')) {
            return;
        }

        Schema::table('other_teacher_tasks', function (Blueprint $table) {
            $table->dropForeign(['decree_id']);
            $table->dropIndex('ott_decree_active_idx');
            $table->dropColumn('decree_id');
        });
    }
};
