<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_universal', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            // MODULE & REFERENCE (Generic)
            $table->string('module'); // recruitment, gtk, work_unit, career, approval, system
            $table->string('reference_type')->nullable(); // model class name
            $table->uuid('reference_id')->nullable(); // model id
            $table->string('reference_code')->nullable(); // nomor/ kode referensi

            // TYPE & ACTION
            $table->string('type'); // info, success, warning, error
            $table->string('action'); // created, updated, deleted, approved, rejected, submitted, verified, etc

            // CONTENT (Flexible)
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // additional data

            // STATUS
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();

            // DELIVERY STATUS
            $table->boolean('is_email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('is_whatsapp_sent')->default(false);
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->boolean('is_push_sent')->default(false);
            $table->timestamp('push_sent_at')->nullable();

            // ACTION URL
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();

            // PRIORITY
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // EXPIRY
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // INDEXES
            $table->index('user_id');
            $table->index('module');
            $table->index('type');
            $table->index('is_read');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_universal');
    }
};
