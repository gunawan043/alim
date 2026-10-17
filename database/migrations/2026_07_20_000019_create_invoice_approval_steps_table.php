<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_approval_id');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->integer('step_order');
            $table->string('role_required')->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'skipped',
            ])->default('pending');
            $table->text('approval_comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('invoice_approval_id')
                ->references('id')
                ->on('invoice_approvals')
                ->cascadeOnDelete();

            $table->index('status');
            $table->index('step_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_approval_steps');
    }
};
