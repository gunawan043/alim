<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_snapshots_archive', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('scope_key', 255);
            $table->unsignedBigInteger('scope_school_id');
            $table->char('fingerprint', 64);
            $table->json('permissions')->nullable();
            $table->json('revoked')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('archived_at')->useCurrent();

            $table->index(['user_id', 'scope_key'], 'idx_snapshots_archive_user_scope');
            $table->index(['user_id', 'is_current'], 'idx_snapshots_archive_user_current');
            $table->index('fingerprint', 'idx_snapshots_archive_fingerprint');
            $table->index('created_at', 'idx_snapshots_archive_created_at');
            $table->index('archived_at', 'idx_snapshots_archive_archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_snapshots_archive');
    }
};