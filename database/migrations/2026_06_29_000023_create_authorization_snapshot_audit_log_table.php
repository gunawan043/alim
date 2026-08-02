<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshot_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope_key', 255);
            $table->string('event', 64);
            $table->char('fingerprint', 64);
            $table->string('status', 32);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'scope_key'], 'idx_audit_user_scope');
            $table->index('event', 'idx_audit_event');
            $table->index('status', 'idx_audit_status');
            $table->index('created_at', 'idx_audit_created_at');
            $table->index('fingerprint', 'idx_audit_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_audit_log');
    }
};
