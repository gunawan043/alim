<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'tikrar_repetitions' => fn (Blueprint $table) => $table->unsignedSmallInteger('tikrar_repetitions')->nullable()->after('tikrar_task'),
            'robet_status' => fn (Blueprint $table) => $table->string('robet_status', 30)->nullable()->after('nilai_tikrar'),
        ] as $column => $definition) {
            if (! Schema::hasColumn('tahfidz_setorans', $column)) {
                Schema::table('tahfidz_setorans', $definition);
            }
        }
    }

    public function down(): void
    {
        foreach (['robet_status', 'tikrar_repetitions'] as $column) {
            if (Schema::hasColumn('tahfidz_setorans', $column)) {
                Schema::table('tahfidz_setorans', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
