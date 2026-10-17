<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SISTEM INVENTARISASI ARSIP — Registri Dokumen Arsip.
 *
 * Menyimpan dokumen final/snapshot yang harus tetap merepresentasikan kondisi
 * saat diterbitkan (mis. snapshot nilai saat buku dikunci) beserta hash SHA-256
 * untuk verifikasi integritas berkas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('archive_documents')) {
            Schema::create('archive_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->cascadeOnDelete();
                $table->enum('semester', ['ganjil', 'genap'])->nullable();
                $table->string('type', 50)->comment('snapshot_nilai, rapor, leger, sk, dll.');
                $table->string('title');
                $table->string('file_path');
                $table->string('file_hash', 64)->comment('SHA-256 isi berkas');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('record_type', 191)->nullable();
                $table->uuid('record_id')->nullable();
                $table->foreignUuid('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignUuid('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
                $table->foreignUuid('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['academic_year_id', 'type'], 'archive_docs_ay_type_idx');
                $table->index(['school_id', 'academic_year_id'], 'archive_docs_school_ay_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_documents');
    }
};
