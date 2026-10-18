<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_chat_conversations', 'mode')) {
            Schema::table('ai_chat_conversations', function (Blueprint $table) {
                $table->string('mode', 20)->default('ai')->index()->after('model');
                $table->string('status', 20)->default('open')->index()->after('mode');
                $table->timestamp('closed_at')->nullable()->after('last_message_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_chat_conversations', 'mode')) {
            Schema::table('ai_chat_conversations', function (Blueprint $table) {
                $table->dropColumn(['mode', 'status', 'closed_at']);
            });
        }
    }
};
