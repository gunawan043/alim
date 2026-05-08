<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'school_code')) {
                $table->string('school_code', 20)->nullable()->after('npsn');
            }
            if (!Schema::hasColumn('schools', 'kop_path')) {
                $table->string('kop_path', 255)->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('schools', 'ttd_ksp_path')) {
                $table->string('ttd_ksp_path', 255)->nullable()->after('kop_path');
            }
            if (!Schema::hasColumn('schools', 'stamp_path')) {
                $table->string('stamp_path', 255)->nullable()->after('ttd_ksp_path');
            }
            if (!Schema::hasColumn('schools', 'kop_nama')) {
                $table->string('kop_nama', 255)->nullable()->after('school_code');
            }
            if (!Schema::hasColumn('schools', 'kop_alamat')) {
                $table->text('kop_alamat')->nullable()->after('kop_nama');
            }
            if (!Schema::hasColumn('schools', 'kop_telp')) {
                $table->string('kop_telp', 50)->nullable()->after('kop_alamat');
            }
            if (!Schema::hasColumn('schools', 'kop_email')) {
                $table->string('kop_email', 100)->nullable()->after('kop_telp');
            }
            if (!Schema::hasColumn('schools', 'kop_website')) {
                $table->string('kop_website', 100)->nullable()->after('kop_email');
            }
            if (!Schema::hasColumn('schools', 'kop_npsn')) {
                $table->string('kop_npsn', 20)->nullable()->after('kop_website');
            }
            if (!Schema::hasColumn('schools', 'kopsis_active')) {
                $table->boolean('kopsis_active')->default(true)->after('kop_npsn');
            }
            if (!Schema::hasColumn('schools', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('established_decree');
            }
            if (!Schema::hasColumn('schools', 'bank_cabang')) {
                $table->string('bank_cabang', 100)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('schools', 'bank_rekening')) {
                $table->string('bank_rekening', 50)->nullable()->after('bank_cabang');
            }
            if (!Schema::hasColumn('schools', 'bank_an')) {
                $table->string('bank_an', 100)->nullable()->after('bank_rekening');
            }
            if (!Schema::hasColumn('schools', 'npwp')) {
                $table->string('npwp', 30)->nullable()->after('bank_an');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $cols = [
                'school_code', 'kop_path', 'ttd_ksp_path', 'stamp_path',
                'kop_nama', 'kop_alamat', 'kop_telp', 'kop_email',
                'kop_website', 'kop_npsn', 'kopsis_active',
                'bank_name', 'bank_cabang', 'bank_rekening', 'bank_an', 'npwp',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('schools', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
