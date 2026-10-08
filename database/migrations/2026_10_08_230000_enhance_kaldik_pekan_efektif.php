<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fondasi Kalender Pendidikan → Pekan Efektif:
     *  - kaldik.semester                        : dukung semester ganjil/genap
     *  - kaldik.type (+)                        : libur, ujian, kegiatan, hari_efektif
     *  - academic_years.semester_{ganjil,genap}_{start,end} : rentang semester (override opsional)
     *  - pekan_efektif.jumlah_hari/is_generated/generated_at : hasil turunan dari kalender
     */
    public function up(): void
    {
        if (! Schema::hasColumn('kaldik', 'semester')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->enum('semester', ['ganjil', 'genap'])->nullable()->after('category');
            });
        }

        // Perluas enum type (khusus MySQL; sqlite menyimpan enum sebagai varchar).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `kaldik` MODIFY `type` ENUM('tahunan','mid_semester','lainnya','libur','ujian','kegiatan','hari_efektif') NULL");
        }

        foreach (['semester_ganjil_start', 'semester_ganjil_end', 'semester_genap_start', 'semester_genap_end'] as $col) {
            if (! Schema::hasColumn('academic_years', $col)) {
                Schema::table('academic_years', function (Blueprint $table) use ($col) {
                    $table->date($col)->nullable()->after('end_date');
                });
            }
        }

        if (! Schema::hasColumn('pekan_efektif', 'jumlah_hari')) {
            Schema::table('pekan_efektif', function (Blueprint $table) {
                $table->unsignedTinyInteger('jumlah_hari')->nullable()->after('tanggal_selesai')
                    ->comment('Jumlah hari efektif dalam pekan');
                $table->boolean('is_generated')->default(false)->after('keterangan')
                    ->comment('True bila dihasilkan dari Kalender Pendidikan');
                $table->timestamp('generated_at')->nullable()->after('is_generated');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `kaldik` MODIFY `type` ENUM('tahunan','mid_semester','lainnya') NULL");
        }

        if (Schema::hasColumn('kaldik', 'semester')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }

        foreach (['semester_ganjil_start', 'semester_ganjil_end', 'semester_genap_start', 'semester_genap_end'] as $col) {
            if (Schema::hasColumn('academic_years', $col)) {
                Schema::table('academic_years', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }

        if (Schema::hasColumn('pekan_efektif', 'jumlah_hari')) {
            Schema::table('pekan_efektif', function (Blueprint $table) {
                $table->dropColumn(['jumlah_hari', 'is_generated', 'generated_at']);
            });
        }
    }
};
