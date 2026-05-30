<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ── Wali Santri pivot (M:N User ↔ Student) ─────────────────────────────
        Schema::create('wali_santri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('role', 20)->default('wali'); // ayah|ibu|kakek|nenek|wali|lainnya
            $table->boolean('is_primary')->default(false);
            $table->string('access_token', 64)->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|active|suspended
            $table->timestamps();

            // Satu user tidak bisa duplikasi peran yang sama untuk student yang sama
            $table->unique(['user_id', 'student_id', 'role']);
            $table->index('user_id');
            $table->index('student_id');
            $table->index('status');
        });

        // ── Registration / Linking tokens ──────────────────────────────────────
        Schema::create('wali_registration_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 64)->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // nikSantri: NIK yang diklaim oleh wali
            $table->string('nik_santri', 30);
            $table->string('no_kk', 30)->nullable();
            // intent: 'link_santri' = hubungkan ke student sudah ada
            //         'register_new' = daftarkan student baru + hubungkan
            //         'add_wali' = minta jadi wali student yang sudah punya wali
            $table->string('intent', 20);
            $table->uuid('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index('token');
        });

        // ── Extend users table: mobile-specific fields ──────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 191)->nullable()->unique()->after('email');
            $table->string('no_kk', 30)->nullable()->change();
            $table->string('nik_wali', 30)->nullable()->change();
            $table->string('no_hp', 20)->nullable()->change();
            $table->string('hubungan', 20)->nullable()->change(); // ayah|ibu|kakek|nenek|wali|lainnya
            $table->boolean('is_wali')->default(false)->after('is_active');
        });

        // ── Extend students table: mobile link tracking ──────────────────────────
        Schema::table('students', function (Blueprint $table) {
            // Hapus nullable constraint agar student bisa ada tanpa user_id
            // Ini akan dilakukan terpisah — tidak di-migrate karena kolom sudah ada
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'is_wali']);
        });
        Schema::dropIfExists('wali_registration_tokens');
        Schema::dropIfExists('wali_santri');
    }
};
