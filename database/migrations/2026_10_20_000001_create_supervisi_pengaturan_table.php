<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supervisi_pengaturan')) {
            return;
        }

        Schema::create('supervisi_pengaturan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('supervisor_gtk_id')->constrained('gtk_profiles')->cascadeOnDelete();
            $table->foreignUuid('supervisee_gtk_id')->constrained('gtk_profiles')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['school_id', 'academic_year_id', 'supervisor_gtk_id', 'supervisee_gtk_id'],
                'supervisi_pengaturan_unique'
            );
            $table->index(['school_id', 'academic_year_id', 'is_active'], 'supervisi_pengaturan_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisi_pengaturan');
    }
};
