<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ekosistem Kurikulum & Perangkat Pembelajaran:
 *
 *  Kalender → Pekan Efektif → JP Efektif → Kurikulum → CP → TP → ATP → Perangkat
 *
 * Additive: memakai struktur existing (subjects, grade_levels, academic_years,
 * study_groups, teaching_assignments, tujuan_pembelajaran) tanpa duplikasi
 * sumber data dan tanpa menghapus kolom/tabel lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Fase pada jenjang (data-driven; tidak di-hardcode di kode) ──
        if (! Schema::hasColumn('grade_levels', 'fase')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->string('fase', 5)->nullable()->after('code')
                    ->comment('Fase capaian pembelajaran, mis. A–F (diisi per jenjang)');
            });
        }

        // ── CP: Capaian Pembelajaran (per mapel + fase) ────────────────
        if (! Schema::hasTable('capaian_pembelajaran')) {
            Schema::create('capaian_pembelajaran', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id')->nullable();
                $table->uuid('subject_id');
                $table->string('fase', 5);
                $table->string('elemen', 100)->nullable();
                $table->text('deskripsi');
                $table->unsignedInteger('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['subject_id', 'fase'], 'cp_subject_fase_idx');
                $table->index(['school_id', 'subject_id', 'fase'], 'cp_school_subject_fase_idx');
            });
        }

        // ── TP sudah ada; tambahkan tautan ke CP (TP diturunkan dari CP) ─
        if (! Schema::hasColumn('tujuan_pembelajaran', 'capaian_pembelajaran_id')) {
            Schema::table('tujuan_pembelajaran', function (Blueprint $table) {
                $table->uuid('capaian_pembelajaran_id')->nullable()->after('subject_id');
                $table->index('capaian_pembelajaran_id', 'tp_cp_idx');
            });
        }

        // ── ATP: Alur Tujuan Pembelajaran ───────────────────────────────
        if (! Schema::hasTable('alur_tujuan_pembelajaran')) {
            Schema::create('alur_tujuan_pembelajaran', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('academic_year_id');
                $table->enum('semester', ['ganjil', 'genap']);
                $table->uuid('subject_id');
                $table->uuid('grade_level_id')->nullable();
                $table->string('fase', 5)->nullable();
                $table->uuid('teacher_id')->nullable();
                $table->unsignedInteger('total_jp')->default(0)
                    ->comment('Total JP alokasi TP pada ATP (dihitung dari item)');
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->text('catatan')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(
                    ['school_id', 'academic_year_id', 'semester', 'subject_id', 'grade_level_id'],
                    'atp_unique_per_school_ay_semester_subject_grade'
                );
            });
        }

        if (! Schema::hasTable('alur_tujuan_pembelajaran_items')) {
            Schema::create('alur_tujuan_pembelajaran_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('alur_tujuan_pembelajaran_id');
                $table->uuid('tujuan_pembelajaran_id');
                $table->unsignedInteger('urutan')->default(0);
                $table->unsignedInteger('jp_alokasi')->default(0);
                $table->string('catatan', 255)->nullable();
                $table->timestamps();

                $table->foreign('alur_tujuan_pembelajaran_id', 'atp_items_atp_fk')
                    ->references('id')->on('alur_tujuan_pembelajaran')->cascadeOnDelete();
                $table->foreign('tujuan_pembelajaran_id', 'atp_items_tp_fk')
                    ->references('id')->on('tujuan_pembelajaran')->cascadeOnDelete();
                $table->unique(
                    ['alur_tujuan_pembelajaran_id', 'tujuan_pembelajaran_id'],
                    'atp_items_unique_tp'
                );
            });
        }

        // ── Perangkat Pembelajaran (fondasi modul ajar dari ATP) ────────
        if (! Schema::hasTable('perangkat_pembelajaran')) {
            Schema::create('perangkat_pembelajaran', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('academic_year_id');
                $table->enum('semester', ['ganjil', 'genap']);
                $table->uuid('subject_id');
                $table->uuid('grade_level_id')->nullable();
                $table->uuid('study_group_id')->nullable();
                $table->uuid('atp_id')->nullable();
                $table->uuid('teacher_id')->nullable();
                $table->string('judul');
                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->json('desain')->nullable()
                    ->comment('Desain pembelajaran mendalam: pertanyaan pemantik, pemahaman bermakna, pengalaman memahami/mengaplikasi/merefleksi, konteks nyata, asesmen, diferensiasi');
                $table->text('catatan')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                $table->foreign('study_group_id')->references('id')->on('study_groups')->nullOnDelete();
                $table->foreign('atp_id')->references('id')->on('alur_tujuan_pembelajaran')->nullOnDelete();
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['school_id', 'academic_year_id', 'semester'], 'perangkat_school_ay_semester_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat_pembelajaran');
        Schema::dropIfExists('alur_tujuan_pembelajaran_items');
        Schema::dropIfExists('alur_tujuan_pembelajaran');

        if (Schema::hasColumn('tujuan_pembelajaran', 'capaian_pembelajaran_id')) {
            Schema::table('tujuan_pembelajaran', function (Blueprint $table) {
                $table->dropIndex('tp_cp_idx');
                $table->dropColumn('capaian_pembelajaran_id');
            });
        }

        Schema::dropIfExists('capaian_pembelajaran');

        if (Schema::hasColumn('grade_levels', 'fase')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->dropColumn('fase');
            });
        }
    }
};
