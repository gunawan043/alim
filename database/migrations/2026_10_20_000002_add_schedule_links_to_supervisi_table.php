<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisi', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisi', 'jadwal_kbm_id')) {
                $table->foreignUuid('jadwal_kbm_id')->nullable()->after('mata_pelajaran')
                    ->constrained('jadwal_kbms')->nullOnDelete();
            }
            if (! Schema::hasColumn('supervisi', 'study_group_id')) {
                $table->foreignUuid('study_group_id')->nullable()->after('jadwal_kbm_id')
                    ->constrained('study_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('supervisi', 'subject_id')) {
                $table->foreignUuid('subject_id')->nullable()->after('study_group_id')
                    ->constrained('subjects')->nullOnDelete();
            }
            if (! Schema::hasColumn('supervisi', 'slot_index')) {
                $table->unsignedSmallInteger('slot_index')->nullable()->after('subject_id');
            }
            if (! Schema::hasColumn('supervisi', 'reported_to_wadir_at')) {
                $table->timestamp('reported_to_wadir_at')->nullable();
            }
            if (! Schema::hasColumn('supervisi', 'reflection_notes')) {
                $table->text('reflection_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supervisi', function (Blueprint $table) {
            foreach (['jadwal_kbm_id', 'study_group_id', 'subject_id', 'slot_index', 'reported_to_wadir_at', 'reflection_notes'] as $column) {
                if (Schema::hasColumn('supervisi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
