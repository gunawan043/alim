<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_message_logs') && ! Schema::hasColumn('wa_message_logs', 'provider_message_id')) {
            Schema::table('wa_message_logs', function (Blueprint $table) {
                $table->string('provider_message_id', 191)->nullable()->after('provider');
                $table->index('provider_message_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wa_message_logs') && Schema::hasColumn('wa_message_logs', 'provider_message_id')) {
            Schema::table('wa_message_logs', function (Blueprint $table) {
                $table->dropIndex(['provider_message_id']);
                $table->dropColumn('provider_message_id');
            });
        }
    }
};
