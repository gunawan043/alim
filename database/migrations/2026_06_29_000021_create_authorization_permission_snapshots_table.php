<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization.permission_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope_key', 255);
            $table->foreignUuid('scope_school_id')->constrained('work_units')->cascadeOnDelete();
            $table->char('fingerprint', 64);
            $table->jsonb('permissions')->nullable();
            $table->jsonb('revoked')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('archived_at')->nullable();

            $table->index(['user_id', 'is_current'], 'idx_snapshots_user_current');
            $table->index('fingerprint', 'idx_snapshots_fingerprint');
            $table->index('created_at', 'idx_snapshots_created_at');
        });

        DB::statement(
            'CREATE UNIQUE INDEX uniq_snapshots_user_scope_current '
            . 'ON authorization.permission_snapshots (user_id, scope_key) '
            . 'WHERE is_current = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization.permission_snapshots');
    }
};