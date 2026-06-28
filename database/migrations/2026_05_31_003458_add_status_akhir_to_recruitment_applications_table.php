<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_applications', 'status_akhir')) {
                $table->string('status_akhir', 30)->nullable()->after('nilai_akhir');
            }
            if (!Schema::hasColumn('recruitment_applications', 'nilai_praktikum')) {
                $table->decimal('nilai_praktikum', 5, 2)->nullable()->after('nilai_wawancara');
            }
            if (!Schema::hasColumn('recruitment_applications', 'tempat_lahir')) {
                $table->string('tempat_lahir', 100)->nullable()->after('catatan_rekruter');
            }
            if (!Schema::hasColumn('recruitment_applications', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn(['status_akhir', 'nilai_praktikum', 'tempat_lahir', 'tanggal_lahir']);
        });
    }
};
