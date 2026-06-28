<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pipeline rapor: setiap siswa yang aktif di sebuah rombel & TA akan otomatis
     * memiliki 1 record di sini (per semester) sebagai "slot" siap cetak rapor.
     *
     * Idempotency key: (student_id, study_group_id, academic_year_id, semester)
     * sehingga event StudentAssignedToRombel yang terpicu berulang tidak akan
     * membuat duplikasi baris.
     */
    public function up(): void
    {
        Schema::create('raport_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('semester', ['ganjil', 'genap']);

            // Status pipeline rapor
            // draft      : placeholder dibuat, belum ada nilai
            // in_progress: ada nilai, rapor sedang disusun guru
            // finalized  : nilai akhir & absensi lengkap, siap cetak
            // printed    : sudah dicetak / diarsipkan
            $table->enum('status', ['draft', 'in_progress', 'finalized', 'printed'])
                ->default('draft');

            // Snapshot nilai akhir (diisi saat rapor final disusun)
            $table->decimal('final_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('class_rank')->nullable();
            $table->string('predicate', 50)->nullable();
            $table->text('homeroom_note')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['student_id', 'study_group_id', 'academic_year_id', 'semester'],
                'unique_raport_per_student_per_rombel_per_ta_per_sem'
            );
            $table->index(['study_group_id', 'academic_year_id', 'semester'], 'idx_raport_pipeline');
            $table->index('status', 'idx_raport_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raport_registrations');
    }
};
