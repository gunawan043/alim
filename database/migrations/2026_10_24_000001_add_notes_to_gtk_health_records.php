<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gtk_health_records') && ! Schema::hasColumn('gtk_health_records', 'notes')) {
            Schema::table('gtk_health_records', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('referral_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gtk_health_records') && Schema::hasColumn('gtk_health_records', 'notes')) {
            Schema::table('gtk_health_records', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
