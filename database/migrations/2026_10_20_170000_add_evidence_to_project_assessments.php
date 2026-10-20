<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curriculum_project_assessments')) {
            return;
        }

        Schema::table('curriculum_project_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('curriculum_project_assessments', 'evidence_note')) {
                $table->text('evidence_note')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('curriculum_project_assessments', 'evidence_url')) {
                $table->string('evidence_url', 500)->nullable()->after('evidence_note');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('curriculum_project_assessments')) {
            return;
        }

        Schema::table('curriculum_project_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('curriculum_project_assessments', 'evidence_url')) {
                $table->dropColumn('evidence_url');
            }
            if (Schema::hasColumn('curriculum_project_assessments', 'evidence_note')) {
                $table->dropColumn('evidence_note');
            }
        });
    }
};
