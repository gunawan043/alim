<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_category_mappings', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('category_slug', 100);
            $table->string('required_skill_slug', 100);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['category_slug', 'required_skill_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_category_mappings');
    }
};