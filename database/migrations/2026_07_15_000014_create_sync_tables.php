<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_tokens', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->unsignedBigInteger('token');
            $table->timestamps();
            $table->index('token');
        });

        Schema::create('user_sync_tokens', function (Blueprint $table) {
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sync_tokens');
        Schema::dropIfExists('sync_tokens');
    }
};