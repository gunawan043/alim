<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 3 — Buku nilai final dikunci + audit:
 *  - Satu buku per konteks akademik (mapel + rombel + TA + semester),
 *    multi-guru berbagi buku yang sama (dijamin di provisi).
 *  - locked_at/locked_by diisi saat nilai final dikunci; perubahan
 *    pasca-kunci hanya setelah dibuka oleh tim kurikulum (ber-audit).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('teacher_admin_books', 'locked_at')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->timestamp('locked_at')->nullable()->after('is_active');
                $table->uuid('locked_by')->nullable()->after('locked_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teacher_admin_books', 'locked_at')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->dropColumn(['locked_at', 'locked_by']);
            });
        }
    }
};
