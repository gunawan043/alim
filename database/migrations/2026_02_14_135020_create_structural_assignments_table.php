<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structural_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('position_id')
                ->constrained('structural_positions')
                ->cascadeOnDelete();

            $table->foreignUuid('school_id')
                ->constrained('schools')
                ->cascadeOnDelete();

            $table->foreignUuid('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

            $table->foreignUuid('decree_id')
                ->constrained('institution_decrees')
                ->cascadeOnDelete();

            // Data penempatan
            $table->string('additional_info', 255)->nullable(); // Info tambahan

            // Masa tugas
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'ended'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'position_id', 'academic_year_id', 'school_id'], 'unique_structural');
            $table->index('user_id');
            $table->index('position_id');
            $table->index('school_id');
            $table->index('academic_year_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structural_assignments');
    }
};
