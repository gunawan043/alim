<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend `users` table with mobile-wali columns ──────────────────
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 191)->nullable()->unique()->after('avatar');
            }
            if (! Schema::hasColumn('users', 'no_kk')) {
                $table->string('no_kk', 30)->nullable()->after('google_id');
            }
            if (! Schema::hasColumn('users', 'nik_wali')) {
                $table->string('nik_wali', 30)->nullable()->after('no_kk');
            }
            if (! Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('nik_wali');
            }
            if (! Schema::hasColumn('users', 'hubungan')) {
                $table->enum('hubungan', ['ayah', 'ibu', 'kakek', 'nenek', 'wali', 'lainnya'])
                    ->nullable()
                    ->after('no_hp');
            }
            if (! Schema::hasColumn('users', 'is_wali')) {
                $table->boolean('is_wali')->default(false)->after('hubungan');
            }
            if (! Schema::hasColumn('users', 'google_token')) {
                $table->text('google_token')->nullable()->after('is_wali');
            }
        });

        // ── 2. Create `wali_santri` pivot table ───────────────────────────────
        // Note: we KEEP students.user_id as-is (nullable denormalized pointer to primary wali).
        // The M:N relationship is fully handled by this pivot table.
        Schema::create('wali_santri', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->string('role', 20)->default('wali');
            $table->boolean('is_primary')->default(false);
            $table->string('access_token', 64)->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 20)->default('pending');

            $table->timestamps();

            // One user cannot have duplicate roles for the same student
            $table->unique(['user_id', 'student_id', 'role'], 'wali_santri_unique');

            $table->index('user_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_santri');

        Schema::table('users', function (Blueprint $table) {
            $columns = ['google_id', 'no_kk', 'nik_wali', 'no_hp', 'hubungan', 'is_wali', 'google_token'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
