<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_transfer_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('from_work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignUuid('to_work_unit_id')->constrained('work_units')->cascadeOnDelete();

            $table->string('jabatan')->nullable();
            $table->text('reason')->nullable();

            // STATUS FLOW
            $table->enum('status', [
                'PENDING',
                'APPROVED',
                'REJECTED',
                'CANCELLED'
            ])->default('PENDING');

            // APPROVAL
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();

            // SECURITY
            $table->ipAddress('request_ip')->nullable();
            $table->string('request_user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_transfer_requests');
    }
};