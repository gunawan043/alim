<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_requirement_standards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id')->nullable();
            $table->string('mapel_category')->nullable()->comment('IPA|IPS|Bahasa|Agama|Umum|Kejuruan');
            $table->string('level_level')->nullable()->comment('grade_level.level (e.g., 7, 8, 9, X, XI, XII)');
            $table->string('level_peminatan')->nullable()->comment('IPA|IPS|Bahasa');
            $table->decimal('ratio_student_teacher', 8, 2)->default(15.00);
            $table->decimal('min_hours_per_week', 6, 2)->default(12.00);
            $table->decimal('max_hours_per_week', 6, 2)->default(24.00);
            $table->string('subject_type')->default('core');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools');
            $table->index(['school_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_requirement_standards');
    }
};
