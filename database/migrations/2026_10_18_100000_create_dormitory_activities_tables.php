<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_activities')) {
            Schema::create('dormitory_activities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('name');
                $table->date('activity_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->foreignUuid('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
                $table->timestamps();

                $table->index(['dormitory_id', 'activity_date']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('dormitory_activity_attendances')) {
            Schema::create('dormitory_activity_attendances', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('activity_id')->constrained('dormitory_activities')->cascadeOnDelete();
                $table->foreignUuid('resident_id')->constrained('dormitory_residents')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('room_id')->constrained('dormitory_rooms')->cascadeOnDelete();
                $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
                $table->text('notes')->nullable();
                $table->foreignUuid('recorded_by')->nullable()->constrained('users', 'id')->nullOnDelete();
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();

                $table->unique(['activity_id', 'resident_id'], 'unique_activity_resident');
                $table->index(['activity_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_activity_attendances');
        Schema::dropIfExists('dormitory_activities');
    }
};
