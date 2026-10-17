<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repair migration — fills the gap left by the partially-applied
        // wali migration (2026_05_29_000002_create_wali_santri_and_extend_users_table.php).
        //
        // Live DB state observed at 2026-06-19:
        //  - users table: has the 7 wali columns (google_id, no_kk, nik_wali, no_hp,
        //    hubungan, is_wali, google_token) ✓
        //  - students table: MISSING wali_status, wali_user_id, wali_linked_at ✗
        //  - wali_santri table: MISSING ✗
        //  - student_wali_requests table: MISSING ✗
        //  - wali_registration_tokens table: MISSING ✗
        //
        // All operations here are guarded with hasTable / hasColumn so they are
        // safe to run on a DB that already has the structures.

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'wali_status')) {
                $table->enum('wali_status', ['unlinked', 'pending', 'linked'])
                    ->default('unlinked');
            }
            if (! Schema::hasColumn('students', 'wali_linked_at')) {
                $table->timestamp('wali_linked_at')->nullable();
            }
            if (! Schema::hasColumn('students', 'wali_user_id')) {
                $table->char('wali_user_id', 36)->nullable();
                $table->index('wali_user_id', 'students_wali_user_id_index');
            }
        });

        if (! Schema::hasTable('wali_santri')) {
            Schema::create('wali_santri', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->char('user_id', 36);
                $table->char('student_id', 36)->nullable();
                $table->string('student_nik', 30)->nullable();
                $table->char('default_student_id', 36)->nullable();
                $table->string('default_student_nik', 30)->nullable();
                $table->boolean('is_primary')->default(true);
                $table->timestamp('linked_at')->nullable();
                $table->timestamps();

                $table->index('user_id', 'wali_santri_user_id_index');
                $table->index('student_id', 'wali_santri_student_id_index');
            });
        }

        if (! Schema::hasTable('student_wali_requests')) {
            Schema::create('student_wali_requests', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->char('student_id', 36);
                $table->char('requested_wali_user_id', 36)->nullable();
                $table->string('requested_phone', 20)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
                $table->string('token_hash', 64)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->char('responded_by_user_id', 36)->nullable();
                $table->timestamps();

                $table->index('student_id', 'student_wali_requests_student_id_index');
                $table->index('status', 'student_wali_requests_status_index');
            });
        }

        if (! Schema::hasTable('wali_registration_tokens')) {
            Schema::create('wali_registration_tokens', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->string('token_hash', 64)->unique();
                $table->char('student_id', 36);
                $table->string('phone', 20);
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamps();

                $table->index('student_id', 'wali_registration_tokens_student_id_index');
            });
        }
    }

    public function down(): void
    {
        // Drop tables in reverse dependency order
        Schema::dropIfExists('wali_registration_tokens');
        Schema::dropIfExists('student_wali_requests');
        Schema::dropIfExists('wali_santri');

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'wali_user_id')) {
                $table->dropIndex('students_wali_user_id_index');
                $table->dropColumn('wali_user_id');
            }
            if (Schema::hasColumn('students', 'wali_linked_at')) {
                $table->dropColumn('wali_linked_at');
            }
            if (Schema::hasColumn('students', 'wali_status')) {
                $table->dropColumn('wali_status');
            }
        });
    }
};
