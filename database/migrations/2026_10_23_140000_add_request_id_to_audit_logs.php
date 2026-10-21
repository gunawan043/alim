<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('audit_logs', 'request_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('request_id', 64)->nullable()->after('user_agent');
                $table->index('request_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'request_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['request_id']);
                $table->dropColumn('request_id');
            });
        }
    }
};
