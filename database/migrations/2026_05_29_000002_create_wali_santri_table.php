<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend users table ─────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->unique()->nullable()->after('password');
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
                $table->string('hubungan', 30)->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('users', 'is_wali')) {
                $table->boolean('is_wali')->default(false)->after('hubungan');
            }
        });

        // ── 2. Extend students table ─────────────────────────────────────────
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'no_kk')) {
                $table->string('no_kk', 30)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('students', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('status');
            }
        });

        // ── 3. WaliSantri pivot table ─────────────────────────────────────────
        Schema::create('wali_santri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('role', 20)->default('wali');
            $table->boolean('is_primary')->default(false);
            $table->string('access_token', 64)->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            // Satu user tidak bisa duplikasi peran yang sama untuk Santi yang sama
            $table->unique(['user_id', 'student_id', 'role']);
            $table->index('user_id');
            $table->index('student_id');
            $table->index('status');
        });

        // ── 4. WaliRegistrationToken ─────────────────────────────────────────
        Schema::create('wali_registration_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 64)->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nik_santri', 30);
            $table->string('no_kk', 30)->nullable();
            $table->string('intent', 20);
            $table->uuid('student_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_registration_tokens');
        Schema::dropIfExists('wali_santri');

        Schema::table('users', function (Blueprint $table) {
            $columns = ['google_id', 'no_kk', 'nik_wali', 'no_hp', 'hubungan', 'is_wali'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'no_kk')) {
                $table->dropColumn('no_kk');
            }
            if (Schema::hasColumn('students', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};
