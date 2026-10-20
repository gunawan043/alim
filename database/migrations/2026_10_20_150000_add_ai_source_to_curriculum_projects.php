<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curriculum_projects')) {
            Schema::table('curriculum_projects', function (Blueprint $table) {
                if (! Schema::hasColumn('curriculum_projects', 'source')) {
                    $table->string('source', 10)->default('manual')->after('type')
                        ->comment('manual / ai');
                }
                if (! Schema::hasColumn('curriculum_projects', 'ai_generated_content_id')) {
                    $table->uuid('ai_generated_content_id')->nullable()->after('source');
                    $table->index('ai_generated_content_id', 'project_ai_content_idx');
                }
            });
        }

        if (Schema::hasTable('ai_generated_contents')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                if (! Schema::hasColumn('ai_generated_contents', 'project_type')) {
                    $table->string('project_type', 10)->nullable()->after('type');
                }
                if (! Schema::hasColumn('ai_generated_contents', 'saved_project_id')) {
                    $table->uuid('saved_project_id')->nullable()->after('saved_perangkat_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_generated_contents')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                if (Schema::hasColumn('ai_generated_contents', 'saved_project_id')) {
                    $table->dropColumn('saved_project_id');
                }
                if (Schema::hasColumn('ai_generated_contents', 'project_type')) {
                    $table->dropColumn('project_type');
                }
            });
        }

        if (Schema::hasTable('curriculum_projects')) {
            Schema::table('curriculum_projects', function (Blueprint $table) {
                if (Schema::hasColumn('curriculum_projects', 'ai_generated_content_id')) {
                    $table->dropIndex('project_ai_content_idx');
                    $table->dropColumn('ai_generated_content_id');
                }
                if (Schema::hasColumn('curriculum_projects', 'source')) {
                    $table->dropColumn('source');
                }
            });
        }
    }
};
