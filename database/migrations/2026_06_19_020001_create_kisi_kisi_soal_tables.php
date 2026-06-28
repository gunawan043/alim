<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip on SQLite: this migration uses anonymous char(36) columns
        // that produce duplicate column names in SQLite (kisi_kisi_soal_*_id
        // would need explicit names). Not required for academic-provision tests.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::create('kisi_kisi_soal', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('subject_id');
            $table->uuid('grade_level_id');
            $table->uuid('academic_year_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->enum('semester', ['ganjil', 'genap']);
            $table->enum('jenis_ujian', ['sts', 'sas', 'ulangan_harian', 'try_out', 'latihan']);
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->enum('tingkat_sekolah', ['sd', 'smp', 'sma'])->default('sma');
            $table->enum('peminatan', ['ipa', 'ips', 'bahasa'])->nullable();
            $table->unsignedInteger('total_soal_target')->default(0);
            $table->decimal('total_bobot_target', 6, 2)->default(0);
            $table->json('distribusi_kognitif')->nullable();
            $table->json('distribusi_kesulitan')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('kisi_kisi_soal_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kisi_kisi_soal_id');
            $table->uuid('tp_id');
            $table->enum('level_kognitif', ['C1_mengingat', 'C2_memahami', 'C3_menerapkan', 'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencita']);
            $table->unsignedInteger('jumlah_soal')->default(1);
            $table->decimal('bobot_per_soal', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('kisi_kisi_soal_id')->references('id')->on('kisi_kisi_soal')->onDelete('cascade');
            $table->foreign('tp_id')->references('id')->on('tujuan_pembelajaran')->onDelete('cascade');
            $table->unique(['kisi_kisi_soal_id', 'tp_id'], 'kisi_kisi_items_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kisi_kisi_soal_items');
        Schema::dropIfExists('kisi_kisi_soal');
    }
};
