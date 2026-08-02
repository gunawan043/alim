<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_communications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('subject', 200);
            $table->text('message');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('channel', ['chat', 'email', 'phone', 'meeting', 'system']);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type', 50)->nullable();
            $table->string('sender_name', 150)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name', 150)->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->index(['entity_type', 'entity_id']);
            $table->index('direction');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_communications');
    }
};
