<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('institution_decrees')) {
            Schema::table('institution_decrees', function (Blueprint $table) {
                if (! Schema::hasColumn('institution_decrees', 'work_unit_id')) {
                    $table->uuid('work_unit_id')->nullable()->after('school_id');
                }
                if (! Schema::hasColumn('institution_decrees', 'semester')) {
                    $table->string('semester', 20)->nullable()->after('academic_year_id');
                }
                if (! Schema::hasColumn('institution_decrees', 'revision_of_id')) {
                    $table->uuid('revision_of_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn('institution_decrees', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('revision_of_id');
                }
                if (! Schema::hasColumn('institution_decrees', 'reviewed_by')) {
                    $table->uuid('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->text('review_notes')->nullable();
                }
                if (! Schema::hasColumn('institution_decrees', 'activated_at')) {
                    $table->timestamp('activated_at')->nullable();
                    $table->uuid('deactivated_by')->nullable();
                    $table->timestamp('deactivated_at')->nullable();
                    $table->text('deactivation_reason')->nullable();
                }
            });
        }

        if (! Schema::hasTable('structural_position_work_unit_hours')) {
            Schema::create('structural_position_work_unit_hours', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('structural_position_id')->constrained('structural_positions')->cascadeOnDelete();
                $table->foreignUuid('work_unit_id')->constrained('work_units')->cascadeOnDelete();
                $table->decimal('weekly_hours', 5, 2)->default(0);
                $table->timestamps();
                $table->unique(['structural_position_id', 'work_unit_id'], 'position_work_unit_hours_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('structural_position_work_unit_hours');
        if (Schema::hasTable('institution_decrees')) {
            Schema::table('institution_decrees', function (Blueprint $table) {
                foreach (['deactivation_reason','deactivated_at','deactivated_by','activated_at','review_notes','reviewed_at','reviewed_by','version','revision_of_id','semester','work_unit_id'] as $column) {
                    if (Schema::hasColumn('institution_decrees', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
