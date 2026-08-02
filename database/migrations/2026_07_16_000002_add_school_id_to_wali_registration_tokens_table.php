<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── P0-1: tenant isolation column for wali_registration_tokens ─────────
        // Strategy:
        //  1. Add nullable column + index.
        //  2. Backfill from linked student_id → student.school_id.
        //  3. Abort if orphaned.
        //  4. Tighten NOT NULL + FK.

        Schema::table('wali_registration_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('wali_registration_tokens', 'school_id')) {
                $table->char('school_id', 36)->nullable()->after('student_id');
                $table->index('school_id', 'wali_registration_tokens_school_id_index');
            }
        });

        // Backfill from student (tokens always have student_id).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE wali_registration_tokens wrt
                JOIN students s ON s.id = wrt.student_id
                SET wrt.school_id = s.school_id
                WHERE wrt.school_id IS NULL
                  AND s.school_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE wali_registration_tokens
                SET school_id = (
                    SELECT s.school_id FROM students s
                    WHERE s.id = wali_registration_tokens.student_id
                )
                WHERE school_id IS NULL
                  AND EXISTS (SELECT 1 FROM students s WHERE s.id = wali_registration_tokens.student_id AND s.school_id IS NOT NULL)
            ');
        }

        // Abort if orphaned (should never happen because student_id is already
        // required and FK-constrained — but we enforce nil-proofing anyway).
        $unfilled = DB::table('wali_registration_tokens')->whereNull('school_id')->count();
        if ($unfilled > 0) {
            throw new \RuntimeException(
                "wali_registration_tokens backfill left {$unfilled} rows without school_id. "
                .'Investigate orphaned student_id references before retrying.'
            );
        }

        Schema::table('wali_registration_tokens', function (Blueprint $table) {
            $table->char('school_id', 36)->nullable(false)->change();

            // Composite index: same-school token lookups only.
            $table->index(['school_id', 'student_id'], 'wali_registration_tokens_school_student_index');
        });

        Schema::table('wali_registration_tokens', function (Blueprint $table) {
            $table->foreign('school_id', 'wali_registration_tokens_school_id_foreign')
                ->references('id')->on('schools')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('wali_registration_tokens', function (Blueprint $table) {
                $table->dropForeign('wali_registration_tokens_school_id_foreign');
            });
        } else {
            Schema::table('wali_registration_tokens', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
            });
        }

        Schema::table('wali_registration_tokens', function (Blueprint $table) {
            $table->dropIndex('wali_registration_tokens_school_student_index');
            $table->dropIndex('wali_registration_tokens_school_id_index');
            $table->dropColumn('school_id');
        });
    }
};
