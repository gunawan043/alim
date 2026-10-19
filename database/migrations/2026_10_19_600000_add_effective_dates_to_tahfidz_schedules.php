<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_schedules')) {
            return;
        }

        Schema::table('tahfidz_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('tahfidz_schedules', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('tahfidz_schedules', 'effective_until')) {
                $table->date('effective_until')->nullable()->after('effective_from');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tahfidz_schedules')) {
            return;
        }

        Schema::table('tahfidz_schedules', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'effective_until']);
        });
    }
};
