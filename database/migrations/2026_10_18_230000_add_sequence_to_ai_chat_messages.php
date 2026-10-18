<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_chat_messages', 'sequence')) {
            Schema::table('ai_chat_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('sequence')->nullable()->index()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_chat_messages', 'sequence')) {
            Schema::table('ai_chat_messages', function (Blueprint $table) {
                $table->dropColumn('sequence');
            });
        }
    }
};
