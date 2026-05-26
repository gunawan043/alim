<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_divisi_subscriptions', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('divisi_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('divisi_id')->references('id')->on('divisis')->cascadeOnDelete();
            $table->unique(['user_id', 'divisi_id']);
            $table->index('user_id');
            $table->index('divisi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_divisi_subscriptions');
    }
};