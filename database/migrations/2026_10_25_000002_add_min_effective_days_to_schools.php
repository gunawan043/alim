<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'min_effective_days_per_week')) {
                $table->unsignedTinyInteger('min_effective_days_per_week')
                    ->default(3)
                    ->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'min_effective_days_per_week')) {
                $table->dropColumn('min_effective_days_per_week');
            }
        });
    }
};
