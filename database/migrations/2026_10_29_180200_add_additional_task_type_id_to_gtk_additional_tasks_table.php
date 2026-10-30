<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tugas tambahan berbasis SK juga harus terikat master jenis tugas,
     * bukan teks bebas pada nama_tugas.
     */
    public function up(): void
    {
        if (Schema::hasColumn('gtk_additional_tasks', 'additional_task_type_id')) {
            return;
        }

        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->uuid('additional_task_type_id')->nullable()->after('work_unit_id');
            $table->index('additional_task_type_id', 'gat_task_type_idx');
            $table->foreign('additional_task_type_id', 'gat_task_type_fk')
                ->references('id')
                ->on('additional_task_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->dropForeign('gat_task_type_fk');
            $table->dropIndex('gat_task_type_idx');
            $table->dropColumn('additional_task_type_id');
        });
    }
};
