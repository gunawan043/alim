<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 1 F9 — backward-compatible provenance columns.
        // Nullable FK ke tabel Phase 2+ yang belum ada. Migration ini
        // TIDAK membuat tabel exam_attempts/paket_soal — hanya
        // menambah kolom nullable agar NilaiController@sts/@sas
        // tetap jalan tanpa konflik.
        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->uuid('paket_soal_id')->nullable()->after('ket');
            $table->uuid('exam_attempt_id')->nullable()->after('paket_soal_id');

            $table->index('paket_soal_id', 'admin_nilai_sumatif_paket_idx');
            $table->index('exam_attempt_id', 'admin_nilai_sumatif_attempt_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->dropIndex('admin_nilai_sumatif_paket_idx');
            $table->dropIndex('admin_nilai_sumatif_attempt_idx');
            $table->dropColumn(['paket_soal_id', 'exam_attempt_id']);
        });
    }
};
