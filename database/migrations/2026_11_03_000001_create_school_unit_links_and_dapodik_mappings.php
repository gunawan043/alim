<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_unit_links')) {
            Schema::create('school_unit_links', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('primary_school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('operational_school_id')->constrained('schools')->cascadeOnDelete();
                $table->string('relation_type', 30)->default('operational');
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['primary_school_id', 'operational_school_id'], 'unit_link_unique');
            });
        }

        if (Schema::hasTable('study_groups')) {
            Schema::table('study_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('study_groups', 'unit_type')) {
                    $table->string('unit_type', 20)->default('primary')->after('school_id');
                }
                if (! Schema::hasColumn('study_groups', 'data_owner_school_id')) {
                    $table->foreignUuid('data_owner_school_id')->nullable()->after('unit_type')
                        ->constrained('schools')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('student_dapodik_mappings')) {
            Schema::create('student_dapodik_mappings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->string('dapodik_school_npsn', 20)->nullable();
                $table->string('dapodik_rombel_name', 100)->nullable();
                $table->string('dapodik_rombel_code', 50)->nullable();
                $table->string('dapodik_level', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id'], 'dapodik_mapping_unique');
                $table->index(['dapodik_school_npsn', 'academic_year_id'], 'dapodik_school_year_idx');
            });
        }

        // Hubungkan dua unit yang sudah ada berdasarkan NPSN.
        $primary = DB::table('schools')->where('npsn', '52010203')->first();
        $operational = DB::table('schools')->where('npsn', '52010205')->first();

        if ($primary && $operational) {
            DB::table('school_unit_links')->updateOrInsert(
                ['primary_school_id' => $primary->id, 'operational_school_id' => $operational->id],
                [
                    'id' => (string) Str::uuid(),
                    'relation_type' => 'operational',
                    'is_active' => 1,
                    'notes' => 'SMP & SMA IT Putra sebagai unit operasional non-mondok; data induk di SMP IT Putra.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_dapodik_mappings');
        Schema::dropIfExists('school_unit_links');

        if (Schema::hasTable('study_groups')) {
            Schema::table('study_groups', function (Blueprint $table) {
                if (Schema::hasColumn('study_groups', 'data_owner_school_id')) {
                    $table->dropConstrainedForeignId('data_owner_school_id');
                }
                if (Schema::hasColumn('study_groups', 'unit_type')) {
                    $table->dropColumn('unit_type');
                }
            });
        }
    }
};
