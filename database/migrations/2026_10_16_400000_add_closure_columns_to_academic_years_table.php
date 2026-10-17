<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SISTEM INVENTARISASI ARSIP — Penutupan Periode.
 *
 * locked_at  : periode ditutup resmi (arsip terkunci) — data tidak dapat diubah
 *              meski tahun ajaran diaktifkan kembali, sampai dibuka kuncinya.
 * locked_by  : pengguna berwenang yang menutup periode (audit).
 * lock_notes : alasan/catatan penutupan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('academic_years', 'locked_at')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->timestamp('locked_at')->nullable()->after('is_active');
                $table->foreignUuid('locked_by')->nullable()->after('locked_at')
                    ->constrained('users')->nullOnDelete();
                $table->text('lock_notes')->nullable()->after('locked_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('academic_years', 'locked_at')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropConstrainedForeignId('locked_by');
                $table->dropColumn(['locked_at', 'lock_notes']);
            });
        }
    }
};
