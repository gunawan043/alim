<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SK yang sudah diterbitkan harus dapat dicetak ulang persis seperti saat
     * diterbitkan. Isi matriks (nama, jabatan, mapel, tugas tambahan) dibekukan
     * pada kolom ini, terpisah dari data master yang terus berubah.
     */
    public function up(): void
    {
        if (Schema::hasColumn('institution_decrees', 'matrix_snapshot')) {
            return;
        }

        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->longText('matrix_snapshot')->nullable()->after('document_filename');
            $table->timestamp('matrix_snapshot_at')->nullable()->after('matrix_snapshot');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('institution_decrees', 'matrix_snapshot')) {
            return;
        }

        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->dropColumn(['matrix_snapshot', 'matrix_snapshot_at']);
        });
    }
};
