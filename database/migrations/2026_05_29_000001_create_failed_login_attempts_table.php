<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('email', 255)->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->string('locked_reason', 20)->nullable(); // 'account' | 'ip'
            $table->timestamps();

            $table->index(['ip_address', 'locked_until']);
            $table->index(['email', 'locked_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');
    }
};
