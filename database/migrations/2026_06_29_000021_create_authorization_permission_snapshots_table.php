<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope_key', 255);
            $table->string('scope_school_id', 64)->nullable();
            $table->char('fingerprint', 64);
            $table->json('permissions')->nullable();
            $table->json('revoked')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('archived_at')->nullable();

            $table->index(['user_id', 'is_current'], 'idx_snapshots_user_current');
            $table->index('fingerprint', 'idx_snapshots_fingerprint');
            $table->index('created_at', 'idx_snapshots_created_at');
        });

        $driver = \DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            \DB::statement(
                'CREATE UNIQUE INDEX uniq_snapshots_user_scope_current '
                . 'ON `permission_snapshots` (user_id, scope_key, is_current)'
            );
        } else {
            \DB::statement(
                'CREATE UNIQUE INDEX uniq_snapshots_user_scope_current '
                . 'ON permission_snapshots (user_id, scope_key) '
                . 'WHERE is_current = true'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_snapshots');
    }
};