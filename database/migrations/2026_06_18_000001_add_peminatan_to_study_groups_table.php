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
        if (Schema::hasColumn('study_groups', 'peminatan')) {
            return;
        }

        Schema::table('study_groups', function (Blueprint $table) {
            $table->enum('peminatan', ['ipa', 'ips', 'bahasa'])
                ->nullable()
                ->after('grade_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('study_groups', 'peminatan')) {
            return;
        }

        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropColumn('peminatan');
        });
    }
};
