<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_muqorror_plans')) {
            Schema::create('tahfidz_muqorror_plans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('work_unit_id')->index();
                $table->uuid('school_id')->nullable()->index();
                $table->uuid('tahfidz_package_id')->nullable()->index();
                $table->uuid('grade_level_id')->nullable()->index();
                $table->uuid('academic_year_id')->index();
                $table->string('semester', 10);
                $table->string('jenjang', 10)->nullable();
                $table->string('title', 150);
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tahfidz_muqorror_months')) {
            Schema::create('tahfidz_muqorror_months', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('plan_id')->index();
                $table->unsignedTinyInteger('month');
                $table->unsignedTinyInteger('target_juz_start')->nullable();
                $table->unsignedTinyInteger('target_juz_end')->nullable();
                $table->decimal('target_pages', 6, 1)->nullable();
                $table->unsignedInteger('target_lines_month')->nullable();
                $table->decimal('target_lines_day', 5, 1)->nullable();
                $table->unsignedTinyInteger('active_days')->nullable();
                $table->string('from_surah_name', 100)->nullable();
                $table->unsignedInteger('from_ayat')->nullable();
                $table->string('to_surah_name', 100)->nullable();
                $table->unsignedInteger('to_ayat')->nullable();
                $table->string('exam_type', 10)->nullable();
                $table->string('target_semester', 100)->nullable();
                $table->string('target_mid_semester', 100)->nullable();
                $table->unsignedTinyInteger('ziyadah_meetings')->nullable();
                $table->unsignedTinyInteger('rabth_meetings')->nullable();
                $table->unsignedTinyInteger('meeting_no')->nullable();
                $table->text('alumni_material')->nullable();
                $table->text('non_alumni_material')->nullable();
                $table->text('notes')->nullable();
                $table->json('detail')->nullable();
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['plan_id', 'month'], 'tahfidz_muqorror_month_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_muqorror_months');
        Schema::dropIfExists('tahfidz_muqorror_plans');
    }
};
