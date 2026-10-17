<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 14. create_tahfidz_juz_progress_table.php
// Progress per juz per santri — membentuk visualisasi peta 30 juz.
// Otomatis diupdate setiap setoran lulus.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_juz_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->tinyInteger('juz_number')->unsigned()->comment('1-30');
            $table->enum('status', ['belum', 'sedang', 'selesai_ziyadah', 'sudah_tasmi'])->default('belum');
            $table->decimal('halaman_completed', 4, 1)->default(0);
            $table->decimal('total_halaman_juz', 4, 1)->default(20);
            $table->decimal('percentage', 5, 2)->default(0)
                ->comment('Persentase: (halaman_completed / total_halaman_juz) * 100');
            $table->date('ziyadah_started_at')->nullable();
            $table->date('ziyadah_completed_at')->nullable();
            $table->date('last_setoran_date')->nullable();
            $table->decimal('avg_nilai_setoran', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->unique(['student_id', 'juz_number'], 'unique_juz_per_student');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_juz_progress');
    }
};
