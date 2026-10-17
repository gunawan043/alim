<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank Soal Terpusat Lintas Satuan Pendidikan.
 *
 * Fondasi workflow:
 *   Bank (repo terpusat) → Soal (terstruktur) → Similarity Check →
 *   Review Serumpun → Approval (semua validator) → Paket Soal →
 *   Quality Gate Paket → Approval Paket → Distribusi → TU (cetak/perbanyak)
 *   → Asesmen Sumatif (paket_soal_id) → Buku Administrasi → Leger → Rapor.
 *
 * Additive: memakai struktur existing (bank_soal, soal, soal_options,
 * paket_soal, soal_clone_log) tanpa menghapus kolom/tabel lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Bank Soal: konteks serumpun lintas satuan pendidikan ──────
        if (! Schema::hasColumn('bank_soal', 'jenjang')) {
            Schema::table('bank_soal', function (Blueprint $table) {
                $table->string('jenjang', 20)->nullable()->after('fase');
                $table->uuid('grade_level_id')->nullable()->after('jenjang');
                $table->uuid('academic_year_id')->nullable()->after('grade_level_id');
                $table->string('semester', 10)->nullable()->after('academic_year_id');
                $table->boolean('is_central')->default(false)->after('shared_scope')
                    ->comment('Bank institusi/yayasan lintas satuan pendidikan');

                $table->index('grade_level_id', 'bank_soal_grade_idx');
                $table->index('academic_year_id', 'bank_soal_ay_idx');
                $table->index('is_central', 'bank_soal_central_idx');
            });
        }

        // ── Soal: materi, turunan, workflow & similarity ──────────────
        if (! Schema::hasColumn('soal', 'materi')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->string('materi', 150)->nullable()->after('tp_id');
                $table->text('pembahasan')->nullable()->after('pertanyaan');
                $table->uuid('derived_from_soal_id')->nullable()->after('pembahasan');
                $table->string('workflow_status', 20)->default('draft')->after('status');
                $table->timestamp('similarity_checked_at')->nullable()->after('shingles_hash');
                $table->json('similarity_summary')->nullable()->after('similarity_checked_at');

                $table->index('derived_from_soal_id', 'soal_derived_idx');
                $table->index('workflow_status', 'soal_workflow_idx');
            });
        }

        // ── Review assignments (generik: Soal & Paket Soal) ───────────
        if (! Schema::hasTable('review_assignments')) {
            Schema::create('review_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('reviewable_type', 100);
                $table->uuid('reviewable_id');
                $table->uuid('reviewer_id');
                $table->string('status', 20)->default('pending')->comment('pending|approved|revision');
                $table->text('note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->foreign('reviewer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['reviewable_type', 'reviewable_id', 'reviewer_id'], 'review_unique');
                $table->index(['reviewable_type', 'reviewable_id'], 'review_reviewable_idx');
                $table->index(['reviewer_id', 'status'], 'review_reviewer_idx');
            });
        }

        // ── Hasil similarity per soal (cross-bank/historis) ───────────
        if (! Schema::hasTable('soal_similarities')) {
            Schema::create('soal_similarities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('soal_id');
                $table->uuid('compared_soal_id');
                $table->decimal('score', 5, 2)->default(0)->comment('0-100');
                $table->string('level', 10)->default('text')->comment('exact|text|semantic');
                $table->string('context', 20)->default('review')->comment('review|package');
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();

                $table->foreign('soal_id')->references('id')->on('soal')->cascadeOnDelete();
                $table->foreign('compared_soal_id')->references('id')->on('soal')->cascadeOnDelete();
                $table->unique(['soal_id', 'compared_soal_id'], 'soal_sim_unique');
                $table->index('soal_id', 'soal_sim_soal_idx');
            });
        }

        // ── Paket Soal: approval, quality gate, distribusi ────────────
        if (! Schema::hasColumn('paket_soal', 'workflow_status')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->string('workflow_status', 20)->default('draft')->after('is_published');
                $table->timestamp('approved_at')->nullable()->after('workflow_status');
                $table->uuid('approved_by')->nullable()->after('approved_at');
                $table->timestamp('similarity_checked_at')->nullable()->after('approved_by');
                $table->json('similarity_summary')->nullable()->after('similarity_checked_at');
                $table->timestamp('distributed_at')->nullable()->after('similarity_summary');
            });
        }

        if (! Schema::hasTable('paket_soal_distributions')) {
            Schema::create('paket_soal_distributions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('paket_soal_id');
                $table->uuid('recipient_user_id')->nullable();
                $table->string('recipient_role', 100)->nullable();
                $table->string('recipient_name', 150)->nullable();
                $table->string('status', 20)->default('sent')->comment('sent|received');
                $table->string('note', 255)->nullable();
                $table->timestamp('distributed_at')->nullable();
                $table->uuid('distributed_by')->nullable();
                $table->timestamps();

                $table->foreign('paket_soal_id', 'psd_paket_fk')->references('id')->on('paket_soal')->cascadeOnDelete();
                $table->index('paket_soal_id', 'psd_paket_idx');
            });
        }

        if (! Schema::hasTable('paket_soal_print_jobs')) {
            Schema::create('paket_soal_print_jobs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('paket_soal_id');
                $table->unsignedInteger('jumlah_cetak')->default(0);
                $table->string('status', 20)->default('antri')->comment('antri|proses|selesai');
                $table->date('tanggal_produksi')->nullable();
                $table->string('petugas', 150)->nullable();
                $table->text('catatan')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('paket_soal_id', 'pspj_paket_fk')->references('id')->on('paket_soal')->cascadeOnDelete();
                $table->index('paket_soal_id', 'pspj_paket_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_soal_print_jobs');
        Schema::dropIfExists('paket_soal_distributions');

        if (Schema::hasColumn('paket_soal', 'workflow_status')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->dropColumn(['workflow_status', 'approved_at', 'approved_by', 'similarity_checked_at', 'similarity_summary', 'distributed_at']);
            });
        }

        Schema::dropIfExists('soal_similarities');
        Schema::dropIfExists('review_assignments');

        if (Schema::hasColumn('soal', 'materi')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->dropIndex('soal_derived_idx');
                $table->dropIndex('soal_workflow_idx');
                $table->dropColumn(['materi', 'pembahasan', 'derived_from_soal_id', 'workflow_status', 'similarity_checked_at', 'similarity_summary']);
            });
        }

        if (Schema::hasColumn('bank_soal', 'jenjang')) {
            Schema::table('bank_soal', function (Blueprint $table) {
                $table->dropIndex('bank_soal_grade_idx');
                $table->dropIndex('bank_soal_ay_idx');
                $table->dropIndex('bank_soal_central_idx');
                $table->dropColumn(['jenjang', 'grade_level_id', 'academic_year_id', 'semester', 'is_central']);
            });
        }
    }
};
