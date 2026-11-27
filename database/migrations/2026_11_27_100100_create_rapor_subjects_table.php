<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar mapel yang dicetak di rapor kini ber-scope TINGKAT, bukan rombel.
 *
 * Satu rombel hanya boleh berbeda dari rombel lain dalam hal urutan; daftar
 * mapel dan KKTP berasal dari tingkat. Tabel ini menyimpan pilihan "masuk rapor"
 * per (tingkat × TA × semester), sementara urutan dan status pondok/dinas
 * disimpan pada grade_level_subjects.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rapor_subjects')) {
            return;
        }

        Schema::create('rapor_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            $table->string('semester', 10);
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(
                ['school_id', 'academic_year_id', 'semester', 'grade_level_id', 'subject_id'],
                'rapor_subjects_unique'
            );
            $table->index(['academic_year_id', 'semester', 'grade_level_id'], 'rapor_subjects_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapor_subjects');
    }
};
