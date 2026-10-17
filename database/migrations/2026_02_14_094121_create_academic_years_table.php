<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('name', 50); // 2024/2025
            $table->enum('semester', ['ganjil', 'genap'])->default('ganjil');
            $table->boolean('is_active')->default(false);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('registration_start')->nullable();
            $table->date('registration_end')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'name', 'semester'], 'unique_academic_year');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
