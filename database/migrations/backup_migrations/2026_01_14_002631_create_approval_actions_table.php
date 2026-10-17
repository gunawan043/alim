<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('step_order');
            $table->string('role_name');

            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->enum('action', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamp('action_at')->nullable();
            $table->text('note')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
