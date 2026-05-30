<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Extend users table for mobile wali ──────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 191)->nullable()->unique()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'no_kk')) {
                $table->string('no_kk', 30)->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'nik_wali')) {
                $table->string('nik_wali', 30)->nullable()->after('no_kk');
            }
            if (!Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('nik_wali');
            }
            if (!Schema::hasColumn('users', 'hubungan')) {
                $table->enum('hubungan', ['ayah', 'ibu', 'kakek', 'nenek', 'wali', 'lainnya'])
                      ->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('users', 'is_wali')) {
                $table->boolean('is_wali')->default(false)->after('hubungan');
            }
            if (!Schema::hasColumn('users', 'google_token')) {
                $table->text('google_token')->nullable()->after('is_wali');
            }
        });

        // ── Extend students table for M:N wali ───────────────────────
        Schema::table('students', function (Blueprint $table) {
            // Remove the FK constraint first — we need flexible M:N
            // This is a soft change: we're keeping user_id but it becomes optional hint
            $table->string('wali_status', 20)->default('unlinked')->after('email');
        });

        // ── Create wali_santri pivot M:N ────────────────────────────
        Schema::create('wali_santri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('student_id');
            $table->string('role', 20)->default('wali');
            $table->boolean('is_primary')->default(false);
            $table->string('access_token', 64)->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['user_id', 'student_id', 'role'], 'wali_santri_unique');
            $table->index('user_id');
            $table->index('student_id');
            $table->index('status');
        });

        // ── Migrate existing user_id in students → is_primary wali_santri ─
        DB::statement("
            INSERT INTO wali_santri (id, user_id, student_id, role, is_primary, status, verified_at, created_at, updated_at)
            SELECT
                UUID(), user_id, id, 'ayah', TRUE, 'active', NOW(), NOW(), NOW()
            FROM students
            WHERE user_id IS NOT NULL
        ");

        // ── Create student_wali_requests ─────────────────────────────
        Schema::create('student_wali_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('requester_id');           // user yang mau jadi wali
            $table->string('role', 20)->default('wali');
            $table->string('nik_claimed', 30);      // NIK Santi yang diklaim
            $table->string('no_kk', 30)->nullable();
            $table->string('approval_token', 64)->nullable();  // token dari wali pertama
            $table->boolean('is_approved')->nullable();       // null=pending, true/false
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('decided_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['student_id', 'requester_id', 'role'], 'swr_unique');
            $table->index('student_id');
            $table->index('requester_id');
            $table->index(['is_approved', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_wali_requests');
        Schema::dropIfExists('wali_santri');

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'wali_status')) {
                $table->dropColumn('wali_status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $cols = ['google_id','no_kk','nik_wali','no_hp','hubungan','is_wali','google_token'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) $table->dropColumn($col);
            }
        });
    }
};
