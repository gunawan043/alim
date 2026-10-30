<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak jalur masuk & keluar santri.
 *
 * entry_source : manual | import | mutation | guardian_mobile
 * exit_reason  : mutation | graduation | dropout
 * exit_date    : tanggal efektif keluar
 *
 * Dengan kolom ini setiap santri dapat ditelusuri asal-usulnya dan alasan
 * keluarnya, termasuk santri yang masuk tanpa jalur mutasi (tambah manual,
 * import Excel, registrasi wali mobile).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'entry_source')) {
                $table->string('entry_source', 30)->nullable()->after('entry_grade_level')->index();
            }

            if (! Schema::hasColumn('students', 'exit_reason')) {
                $table->string('exit_reason', 30)->nullable()->index();
            }

            if (! Schema::hasColumn('students', 'exit_date')) {
                $table->date('exit_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['entry_source', 'exit_reason', 'exit_date'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
