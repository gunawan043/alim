<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revoked_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 255);
            $table->string('scope_key', 255);
            $table->text('reason');
            $table->foreignUuid('granted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'scope_key'], 'idx_revocations_user_scope');
            $table->index('permission', 'idx_revocations_permission');
            $table->index('valid_from', 'idx_revocations_valid_from');
            $table->index('valid_until', 'idx_revocations_valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revoked_permissions');
    }
};
