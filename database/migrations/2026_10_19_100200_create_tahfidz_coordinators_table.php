<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_coordinators')) {
            Schema::create('tahfidz_coordinators', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id')->index();
                $table->uuid('user_id')->index();
                $table->uuid('academic_year_id')->index();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'user_id', 'academic_year_id'], 'tahfidz_coordinator_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_coordinators');
    }
};
