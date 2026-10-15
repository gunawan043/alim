<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proventa LJK pada nilai sumatif resmi.
 *
 * Kolom nullable aditif — tidak mengubah struktur/perilaku lama. Dipakai
 * untuk menelusuri kembali nilai yang berasal dari sinkronisasi LJK.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_nilai_sumatif')) {
            return;
        }

        if (! Schema::hasColumn('admin_nilai_sumatif', 'ljk_exam_id')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                $table->uuid('ljk_exam_id')->nullable()->after('exam_attempt_id');
                $table->index('ljk_exam_id', 'admin_nilai_sumatif_ljk_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_nilai_sumatif') && Schema::hasColumn('admin_nilai_sumatif', 'ljk_exam_id')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                $table->dropIndex('admin_nilai_sumatif_ljk_idx');
                $table->dropColumn('ljk_exam_id');
            });
        }
    }
};
