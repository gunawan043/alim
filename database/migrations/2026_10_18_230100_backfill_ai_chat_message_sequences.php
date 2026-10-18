<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_chat_messages', 'sequence')) {
            return;
        }

        $counters = [];

        DB::table('ai_chat_messages')
            ->whereNull('sequence')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'conversation_id'])
            ->each(function ($message) use (&$counters) {
                $conversationId = (string) $message->conversation_id;
                $counters[$conversationId] = ($counters[$conversationId] ?? 0) + 1;

                DB::table('ai_chat_messages')
                    ->where('id', $message->id)
                    ->update(['sequence' => $counters[$conversationId]]);
            });
    }

    public function down(): void
    {
        // Data backfill tidak dibalik.
    }
};
