<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi ke SK
            $table->foreignUuid('decree_id')
                ->constrained('institution_decrees')
                ->cascadeOnDelete();

            // Guru
            $table->foreignUuid('teacher_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Assignment details
            $table->foreignUuid('school_id')
                ->constrained('schools')
                ->cascadeOnDelete();

            $table->foreignUuid('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

            $table->foreignUuid('study_group_id')
                ->constrained('study_groups')
                ->cascadeOnDelete();

            $table->foreignUuid('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            // Peran dalam pengajaran
            $table->enum('role', [
                'guru_mapel',
                'guru_pendamping',
                'guru_praktik',
                'ustadz_pengasuh',
            ])->default('guru_mapel');

            $table->boolean('is_coordinator')->default(false); // Koordinator mapel
            $table->integer('weekly_hours')->default(0);

            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['decree_id', 'teacher_id', 'study_group_id', 'subject_id'], 'unique_teaching_assign');
            $table->index('teacher_id');
            $table->index('study_group_id');
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
    }
};
