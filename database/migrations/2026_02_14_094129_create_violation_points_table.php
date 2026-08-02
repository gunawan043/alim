<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('recorded_by')->constrained('users')->cascadeOnDelete();

            $table->date('violation_date');
            $table->string('violation_type', 100);
            $table->integer('points')->default(0);
            $table->text('description')->nullable();
            $table->text('action_taken')->nullable(); // Tindakan yang diambil

            $table->timestamps();

            $table->index('student_id');
            $table->index('violation_date');
            $table->index('points');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_points');
    }
};
