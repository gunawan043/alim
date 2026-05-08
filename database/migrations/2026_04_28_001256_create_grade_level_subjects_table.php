<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_level_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grade_level_id');
            $table->uuid('subject_id');
            $table->tinyInteger('allocation_hours')->default(0)->comment('Alokasi jam per minggu');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->unique(['grade_level_id', 'subject_id'], 'unique_grade_level_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_level_subjects');
    }
};
