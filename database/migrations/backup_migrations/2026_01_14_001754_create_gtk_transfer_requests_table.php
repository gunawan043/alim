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
        Schema::create('gtk_transfer_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // GTK
            $table->foreignId('from_work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignId('to_work_unit_id')->constrained('work_units')->cascadeOnDelete();

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
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();

            // SECURITY
            $table->ipAddress('request_ip')->nullable();
            $table->string('request_user_agent')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_transfer_requests');
    }
};
