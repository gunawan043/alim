<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Provider push native: fcm (Android) atau apns (iOS).
            $table->string('provider', 20)->nullable();
            // FCM registration token atau APNs device token.
            $table->string('push_token', 512)->nullable();

            $table->string('platform', 20); // android | ios
            $table->string('device_name')->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('device_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
