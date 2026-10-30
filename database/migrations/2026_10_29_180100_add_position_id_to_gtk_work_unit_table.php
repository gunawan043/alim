<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jabatan pada satuan kerja sebelumnya hanya teks bebas di gtk_work_unit.jabatan,
     * tidak terikat master structural_positions. Kolom ini menjadikannya relasi nyata.
     */
    public function up(): void
    {
        if (Schema::hasColumn('gtk_work_unit', 'position_id')) {
            return;
        }

        Schema::table('gtk_work_unit', function (Blueprint $table) {
            $table->uuid('position_id')->nullable()->after('jabatan');
            $table->index('position_id', 'gtk_wu_position_idx');
            $table->foreign('position_id', 'gtk_wu_position_fk')
                ->references('id')
                ->on('structural_positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gtk_work_unit', function (Blueprint $table) {
            $table->dropForeign('gtk_wu_position_fk');
            $table->dropIndex('gtk_wu_position_idx');
            $table->dropColumn('position_id');
        });
    }
};
