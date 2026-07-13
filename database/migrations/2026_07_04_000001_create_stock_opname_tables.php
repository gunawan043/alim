<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('session_code')->unique();
            $table->string('work_unit_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->date('started_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->string('status')->default('planned'); // planned, in_progress, closed, cancelled
            $table->unsignedBigInteger('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('work_unit_id');
        });

        Schema::create('stock_opname_officers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('session_id');
            $table->foreign('session_id')->references('id')->on('stock_opname_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('officer'); // lead, officer
            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('session_id');
            $table->foreign('session_id')->references('id')->on('stock_opname_sessions')->cascadeOnDelete();
            $table->string('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->string('expected_status')->nullable();
            $table->string('observed_status'); // found, missing, damaged, moved
            $table->string('expected_room_id')->nullable();
            $table->string('observed_room_id')->nullable();
            $table->string('condition_observed')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'asset_id']);
            $table->index('observed_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_officers');
        Schema::dropIfExists('stock_opname_sessions');
    }
};