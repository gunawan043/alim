<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boarding_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('boarding_policies', 'guardian_consent_required')) {
                // NULL = ikuti pengaturan global (Pengaturan Sistem → Asrama).
                $table->boolean('guardian_consent_required')->nullable()
                    ->after('max_visitors_per_visit')
                    ->comment('NULL = follow global asrama consent setting.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('boarding_policies', function (Blueprint $table) {
            if (Schema::hasColumn('boarding_policies', 'guardian_consent_required')) {
                $table->dropColumn('guardian_consent_required');
            }
        });
    }
};
