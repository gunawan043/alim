<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4 — audit state machine mutasi:
 * menyimpan waktu pengajuan serta siapa/waktu penolakan agar jejak
 * workflow (draft → submitted → approved/rejected) lengkap.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['student_mutations_in', 'student_mutations_out'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'submitted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('submitted_at')->nullable()->after('status');
                    $table->uuid('rejected_by')->nullable()->after('approved_at');
                    $table->timestamp('rejected_at')->nullable()->after('rejected_by');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['student_mutations_in', 'student_mutations_out'] as $tableName) {
            if (Schema::hasColumn($tableName, 'submitted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn(['submitted_at', 'rejected_by', 'rejected_at']);
                });
            }
        }
    }
};
