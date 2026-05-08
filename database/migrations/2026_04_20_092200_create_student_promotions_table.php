<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tahun ajaran lama → baru
            $table->foreignUuid('from_academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();
            $table->foreignUuid('to_academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

            // Promosi per rombel (null = naik satu level penuh)
            $table->foreignUuid('from_study_group_id')
                ->nullable()
                ->constrained('study_groups')
                ->nullOnDelete();
            $table->foreignUuid('to_study_group_id')
                ->nullable()
                ->constrained('study_groups')
                ->cascadeOnDelete();

            $table->date('promotion_date')->comment('Tanggal efektif promosi');
            $table->enum('status', ['draft', 'processed', 'completed', 'cancelled'])
                ->default('draft');

            $table->boolean('auto_enroll')->default(true)
                ->comment('Auto enroll siswa ke rombel baru');
            $table->boolean('include_inactive')->default(false)
                ->comment('Ikut sertakan siswa non-aktif (dropout/naik kelas)');
            $table->boolean('skip_graduate')->default(true)
                ->comment('Lewati siswa yang sudah graduate');

            // Opsi naik/turun kelas
            $table->integer('grade_shift')->default(1)
                ->comment('Langkah naik kelas (default: +1)');

            // Log
            $table->foreignUuid('executed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('executed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['from_academic_year_id', 'status']);
            $table->index('to_academic_year_id');
        });

        // Detail siswa yang di-promo
        Schema::create('student_promotion_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')
                ->constrained('student_promotions')
                ->cascadeOnDelete();

            $table->foreignUuid('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->enum('action', ['promote', 'retain', 'graduate', 'mutate_out', 'skip'])
                ->default('promote');

            // Status per siswa
            $table->enum('status', ['pending', 'success', 'failed'])
                ->default('pending');
            $table->text('error_message')->nullable();

            // Opsi override: naik berapa level (default 1, tinggal kelas = 0, double skip = 2)
            $table->integer('override_grade_shift')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['promotion_id', 'student_id'], 'promotion_detail_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotion_details');
        Schema::dropIfExists('student_promotions');
    }
};