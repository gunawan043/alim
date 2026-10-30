<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('humas_announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('humas_announcements', 'activity_start_date')) {
                $table->date('activity_start_date')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('humas_announcements', 'activity_end_date')) {
                $table->date('activity_end_date')->nullable()->after('activity_start_date');
            }
        });

        Schema::table('kaldik', function (Blueprint $table) {
            if (! Schema::hasColumn('kaldik', 'source_type')) {
                $table->string('source_type', 150)->nullable()->after('description');
            }
            if (! Schema::hasColumn('kaldik', 'source_id')) {
                $table->uuid('source_id')->nullable()->after('source_type');
                $table->index(['source_type', 'source_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('kaldik', function (Blueprint $table) {
            if (Schema::hasColumn('kaldik', 'source_type')) {
                $table->dropIndex(['source_type', 'source_id']);
                $table->dropColumn(['source_type', 'source_id']);
            }
        });

        Schema::table('humas_announcements', function (Blueprint $table) {
            $table->dropColumn(['activity_start_date', 'activity_end_date']);
        });
    }
};
