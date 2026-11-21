<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive idempotency/audit records for service-to-service approval commands.
     */
    public function up(): void
    {
        Schema::create('approval_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('command_id', 100)->unique();
            $table->foreignUuid('permit_id')->constrained('dormitory_permits')->restrictOnDelete();
            $table->foreignUuid('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_external_identity', 191)
                ->comment('Identitas WhatsApp pengirim setelah normalisasi');
            $table->string('decision', 32);
            $table->string('source', 20);
            $table->string('external_message_id', 191)->nullable()->unique();
            $table->text('message_text')->nullable();
            $table->char('payload_hash', 64);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['permit_id', 'created_at'], 'approval_commands_permit_created_idx');
            $table->index(['actor_user_id', 'created_at'], 'approval_commands_actor_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_commands');
    }
};
