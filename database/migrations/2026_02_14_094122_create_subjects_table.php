<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('code', 20);
            $table->string('name', 100);
            $table->enum('category', ['nasional', 'lokal', 'muatan_lokal'])->default('nasional');
            $table->integer('credit_hours')->default(2); // Jam pelajaran per minggu
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['school_id', 'code'], 'unique_subject_code');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
