<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── P0-1: tenant isolation column for wali_santri ────────────────────
        // Strategy:
        //  1. Add nullable column + index (safe on populated tables).
        //  2. Backfill from students.school_id (the only authoritative source).
        //  3. ABORT if any row is left NULL after backfill (defense in depth).
        //  4. Tighten to NOT NULL + add FK to schools.

        Schema::table('wali_santri', function (Blueprint $table) {
            if (! Schema::hasColumn('wali_santri', 'school_id')) {
                $table->char('school_id', 36)->nullable()->after('student_id');
                $table->index('school_id', 'wali_santri_school_id_index');
            }
        });

        // Backfill. The student's school_id is the canonical tenant — wali_santri
        // belongs to the same tenant as the student it links.
        DB::statement('
            UPDATE wali_santri ws
            JOIN students s ON s.id = ws.student_id
            SET ws.school_id = s.school_id
            WHERE ws.school_id IS NULL
              AND s.school_id IS NOT NULL
        ');

        // Defense in depth: if any row remains NULL (orphaned student_id, missing
        // student.school_id), abort the migration. Silent defaults would create
        // cross-tenant records we cannot reason about.
        $unfilled = DB::table('wali_santri')->whereNull('school_id')->count();
        if ($unfilled > 0) {
            throw new \RuntimeException(
                "wali_santri backfill left {$unfilled} rows without school_id. "
                .'Investigate orphaned student_id references before retrying.'
            );
        }

        Schema::table('wali_santri', function (Blueprint $table) {
            $table->char('school_id', 36)->nullable(false)->change();

            // Composite indexes: tenant-aware lookups replace single-column lookups.
            $table->index(['school_id', 'student_id'], 'wali_santri_school_student_index');
            $table->index(['school_id', 'user_id'], 'wali_santri_school_user_index');
        });

        // FK enforces referential tenant integrity at the storage layer. Even if
        // a bug assigns a school_id that doesn't exist, MySQL rejects the write.
        Schema::table('wali_santri', function (Blueprint $table) {
            $table->foreign('school_id', 'wali_santri_school_id_foreign')
                ->references('id')->on('schools')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wali_santri', function (Blueprint $table) {
            $table->dropForeign('wali_santri_school_id_foreign');
            $table->dropIndex('wali_santri_school_student_index');
            $table->dropIndex('wali_santri_school_user_index');
            $table->dropIndex('wali_santri_school_id_index');
            $table->dropColumn('school_id');
        });
    }
};
