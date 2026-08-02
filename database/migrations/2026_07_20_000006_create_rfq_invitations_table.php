<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('vendor_id');
            $table->enum('status', ['invited', 'viewed', 'declined', 'submitted'])->default('invited');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('rfq_id')
                ->references('id')
                ->on('rfq_requests')
                ->cascadeOnDelete();
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->unique(['rfq_id', 'vendor_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_invitations');
    }
};
