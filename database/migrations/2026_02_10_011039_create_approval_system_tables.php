<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // APPROVAL FLOWS
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // APPROVAL FLOW STEPS
        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_flow_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('role_name');
            $table->unsignedTinyInteger('min_role_level');
            $table->timestamps();
        });

        // APPROVAL REQUESTS
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_type');
            $table->uuid('reference_id');
            $table->foreignUuid('requested_by')->constrained('users');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamps();
        });

        // APPROVAL ACTIONS
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('role_name');
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->enum('action', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamp('action_at')->nullable();
            $table->text('note')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_flow_steps');
        Schema::dropIfExists('approval_flows');
    }
};