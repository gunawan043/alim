<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_assessment_rubrics')) {
            Schema::create('tahfidz_assessment_rubrics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('work_unit_id')->nullable()->index();
                $table->string('name', 100);
                $table->json('components')->nullable();
                $table->decimal('kkm', 5, 2)->default(60);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_assessment_rubrics');
    }
};
