<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boarding_timeline_events', function (Blueprint $table) {
            $table->string('module', 32)->default('boarding')->after('source_actor_id');
            $table->string('category', 32)->default('info')->after('module');
            $table->index(['module', 'category', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::table('boarding_timeline_events', function (Blueprint $table) {
            $table->dropColumn(['module', 'category']);
            $table->dropIndex(['module', 'category', 'event_at']);
        });
    }
};