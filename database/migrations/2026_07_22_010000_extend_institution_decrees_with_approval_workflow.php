<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->foreignUuid('submitted_by')->nullable()->after('submitted_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('submitted_by');
            $table->foreignUuid('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');

            $table->index(['status', 'submitted_at'], 'idx_decree_workflow_status_submitted');
            $table->index(['status', 'approved_at'], 'idx_decree_workflow_status_approved');
            $table->index('submitted_by');
            $table->index('approved_by');
        });

        // SQLite does not support ENUM or MODIFY COLUMN syntax
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE institution_decrees MODIFY COLUMN status
             ENUM('draft', 'submitted', 'pending_review', 'reviewed', 'approved', 'rejected', 'active', 'archived')
             NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE institution_decrees MODIFY COLUMN status
             ENUM('draft', 'active', 'archived')
             NOT NULL DEFAULT 'draft'"
        );

        Schema::table('institution_decrees', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex('idx_decree_workflow_status_submitted');
            $table->dropIndex('idx_decree_workflow_status_approved');
            $table->dropIndex(['submitted_by']);
            $table->dropIndex(['approved_by']);

            $table->dropColumn(['submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'rejection_reason']);
        });
    }
};
