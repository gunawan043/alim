<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->boolean('email_enabled')->default(true);
                $table->boolean('whatsapp_enabled')->default(true);
                $table->boolean('push_enabled')->default(true);
                $table->boolean('lesson_reminder')->default(true);
                $table->boolean('late_notification')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
