<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            $table->integer('level'); // 7,8,9 atau 10,11,12
            $table->string('name', 50); // Kelas 7, Kelas 10
            $table->string('code', 20)->nullable(); // VII, VIII, IX atau X, XI, XII
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['school_id', 'level'], 'unique_grade_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
