<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROTA → PROSEM → RPM (Rencana Pembelajaran Mendalam).
 *
 * Semua sumber data diambil dari modul existing:
 *  - PROTA  : ATP (TP + alokasi JP) + Pekan Efektif (minggu/JP efektif)
 *  - PROSEM : PROTA + Pekan Efektif/Kaldik (minggu efektif, libur, ujian)
 *  - RPM    : Perangkat Pembelajaran (perangkat_pembelajaran) + tipe struktur
 *             (agama/umum) sesuai kebutuhan guru.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── RPM: tipe struktur (Guru Agama / Guru Umum) ────────────────
        if (! Schema::hasColumn('perangkat_pembelajaran', 'tipe')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->enum('tipe', ['umum', 'agama'])->default('umum')->after('judul')
                    ->comment('Struktur RPM: umum (identifikasi + desain lengkap) / agama');
            });
        }

        // ── PROTA ──────────────────────────────────────────────────────
        if (! Schema::hasTable('prota')) {
            Schema::create('prota', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('academic_year_id');
                $table->enum('semester', ['ganjil', 'genap']);
                $table->uuid('subject_id');
                $table->uuid('grade_level_id')->nullable();
                $table->string('fase', 5)->nullable();
                $table->uuid('teacher_id')->nullable();
                $table->uuid('atp_id')->nullable();

                // Snapshot dari Pekan Efektif + resolver JP (indikator perlu diperbarui).
                $table->unsignedInteger('minggu_efektif')->default(0);
                $table->unsignedInteger('jp_per_minggu')->default(0);
                $table->unsignedInteger('jp_efektif')->default(0);
                $table->unsignedInteger('total_jp')->default(0)->comment('Total JP dari item PROTA');
                $table->timestamp('synced_at')->nullable();

                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->text('catatan')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('atp_id')->references('id')->on('alur_tujuan_pembelajaran')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(
                    ['school_id', 'academic_year_id', 'semester', 'subject_id', 'grade_level_id'],
                    'prota_unique_per_school_ay_semester_subject_grade'
                );
            });
        }

        if (! Schema::hasTable('prota_items')) {
            Schema::create('prota_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('prota_id');
                $table->uuid('tujuan_pembelajaran_id')->nullable();
                $table->string('bab', 150)->nullable();
                $table->text('materi')->nullable();
                $table->unsignedInteger('alokasi_jp')->default(0);
                $table->string('keterangan', 255)->nullable();
                $table->unsignedInteger('urutan')->default(0);
                $table->timestamps();

                $table->foreign('prota_id', 'prota_items_prota_fk')->references('id')->on('prota')->cascadeOnDelete();
                $table->foreign('tujuan_pembelajaran_id', 'prota_items_tp_fk')->references('id')->on('tujuan_pembelajaran')->nullOnDelete();
            });
        }

        // ── PROSEM ─────────────────────────────────────────────────────
        if (! Schema::hasTable('prosem')) {
            Schema::create('prosem', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('prota_id');
                $table->uuid('school_id');
                $table->uuid('academic_year_id');
                $table->enum('semester', ['ganjil', 'genap']);
                $table->uuid('subject_id');
                $table->uuid('grade_level_id')->nullable();
                $table->uuid('teacher_id')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->text('catatan')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('prota_id', 'prosem_prota_fk')->references('id')->on('prota')->cascadeOnDelete();
                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->unique('prota_id', 'prosem_unique_prota');
            });
        }

        if (! Schema::hasTable('prosem_items')) {
            Schema::create('prosem_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('prosem_id');
                $table->uuid('prota_item_id')->nullable();
                $table->uuid('tujuan_pembelajaran_id')->nullable();
                $table->unsignedInteger('urutan')->default(0);
                $table->unsignedInteger('mulai_minggu_ke')->default(0);
                $table->unsignedInteger('selesai_minggu_ke')->default(0);
                $table->unsignedInteger('jp')->default(0);
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();

                $table->foreign('prosem_id', 'prosem_items_prosem_fk')->references('id')->on('prosem')->cascadeOnDelete();
                $table->foreign('prota_item_id', 'prosem_items_prota_fk')->references('id')->on('prota_items')->nullOnDelete();
                $table->foreign('tujuan_pembelajaran_id', 'prosem_items_tp_fk')->references('id')->on('tujuan_pembelajaran')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prosem_items');
        Schema::dropIfExists('prosem');
        Schema::dropIfExists('prota_items');
        Schema::dropIfExists('prota');

        if (Schema::hasColumn('perangkat_pembelajaran', 'tipe')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }
    }
};
