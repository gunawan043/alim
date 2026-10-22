<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_chat_messages') || Schema::hasColumn('ai_chat_messages', 'client_message_id')) {
            return;
        }

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->string('client_message_id', 36)->nullable()->unique()->after('conversation_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_chat_messages', 'client_message_id')) {
            Schema::table('ai_chat_messages', function (Blueprint $table) {
                $table->dropUnique(['client_message_id']);
                $table->dropColumn('client_message_id');
            });
        }
    }
};
