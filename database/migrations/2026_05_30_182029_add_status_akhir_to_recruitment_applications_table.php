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
            $table->string('status_akhir')->nullable()->after('status');
            $table->decimal('nilai_praktikum', 5, 2)->nullable()->after('nilai_wawancara');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn(['status_akhir', 'nilai_praktikum']);
        });
    }
};
