<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai per santri untuk buku tahfidz (per halaqah).
 *
 * Empat kolom penilaian mengikuti format tahfidz, semuanya diisi guru
 * (bukan diturunkan dari data setoran):
 *  - ziyadah : rata-rata nilai ziyadah (hafalan baru)
 *  - rabt    : rata-rata nilai rabt (penyambung hafalan)
 *  - sts     : sumatif tengah semester
 *  - sas     : sumatif akhir semester
 *
 * `nr_final` adalah hasil pembobotan yang disimpan agar rapor, leger, dan
 * cetak memakai angka yang sama persis.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tahfidz_gradebook_scores')) {
            return;
        }

        Schema::create('tahfidz_gradebook_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tahfidz_gradebook_id');
            $table->uuid('student_id');
            $table->uuid('study_group_id')->nullable()
                ->comment('Rombel santri saat nilai diisi — tujuan rapor');

            $table->decimal('ziyadah', 5, 2)->nullable();
            $table->decimal('rabt', 5, 2)->nullable();
            $table->decimal('sts', 5, 2)->nullable();
            $table->decimal('sas', 5, 2)->nullable();

            $table->decimal('nr_final', 5, 2)->nullable();
            $table->string('ket', 100)->nullable();
            $table->timestamps();

            $table->foreign('tahfidz_gradebook_id')->references('id')->on('tahfidz_gradebooks')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->nullOnDelete();

            $table->unique(['tahfidz_gradebook_id', 'student_id'], 'unique_tahfidz_score_per_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_gradebook_scores');
    }
};
