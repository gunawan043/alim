<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_activity_schedules')) {
            Schema::create('dormitory_activity_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
                $table->string('name');
                $table->enum('recurrence', ['daily', 'weekly'])->default('daily');
                $table->json('days_of_week')->nullable()->comment('1=Senin ... 7=Minggu, untuk weekly');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->enum('target_type', ['all', 'class'])->default('all');
                $table->json('target_study_group_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignUuid('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
                $table->timestamps();

                $table->index(['dormitory_id', 'is_active']);
            });
        }

        if (Schema::hasTable('dormitory_activities')) {
            Schema::table('dormitory_activities', function (Blueprint $table) {
                if (! Schema::hasColumn('dormitory_activities', 'schedule_id')) {
                    $table->foreignUuid('schedule_id')->nullable()->after('id')
                        ->constrained('dormitory_activity_schedules')->nullOnDelete();
                }
                if (! Schema::hasColumn('dormitory_activities', 'target_type')) {
                    $table->enum('target_type', ['all', 'class'])->default('all')->after('description');
                }
                if (! Schema::hasColumn('dormitory_activities', 'target_study_group_ids')) {
                    $table->json('target_study_group_ids')->nullable()->after('target_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dormitory_activities')) {
            Schema::table('dormitory_activities', function (Blueprint $table) {
                if (Schema::hasColumn('dormitory_activities', 'schedule_id')) {
                    $table->dropConstrainedForeignId('schedule_id');
                }
                foreach (['target_type', 'target_study_group_ids'] as $col) {
                    if (Schema::hasColumn('dormitory_activities', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('dormitory_activity_schedules');
    }
};
