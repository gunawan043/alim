<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel Kisi-Kisi Soal pada instalasi SQLite.
 *
 * Migrasi asli (2026_06_19_020001) sengaja melewati SQLite sehingga
 * produksi yang memakai SQLite tidak pernah memiliki tabel ini — akibatnya
 * Pusat Asesmen dan Buku Nilai Guru (w3) error 500.
 *
 * Migrasi ini portable: hanya membuat tabel yang belum ada, dan tidak
 * mengubah tabel yang sudah dibuat di MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kisi_kisi_soal')) {
            Schema::create('kisi_kisi_soal', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id')->index();
                $table->uuid('bank_soal_id')->nullable()->index();
                $table->uuid('subject_id')->index();
                $table->uuid('grade_level_id')->index();
                $table->uuid('academic_year_id')->nullable()->index();
                $table->uuid('created_by')->nullable()->index();
                $table->uuid('approved_by')->nullable();
                $table->string('semester', 10);
                $table->string('jenis_ujian', 24);
                $table->string('judul', 150);
                $table->text('deskripsi')->nullable();
                $table->string('tingkat_sekolah', 10)->default('sma');
                $table->string('peminatan', 10)->nullable();
                $table->unsignedInteger('total_soal_target')->default(0);
                $table->decimal('total_bobot_target', 6, 2)->default(0);
                $table->text('distribusi_kognitif')->nullable();
                $table->text('distribusi_kesulitan')->nullable();
                $table->text('approver_note')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kisi_kisi_soal_items')) {
            Schema::create('kisi_kisi_soal_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('kisi_kisi_soal_id')->index();
                $table->uuid('tp_id')->nullable()->index();
                $table->string('materi', 255)->nullable();
                $table->string('bentuk_soal', 20)->nullable();
                $table->string('nomor_soal', 40)->nullable();
                $table->string('level_kognitif', 32);
                $table->unsignedInteger('jumlah_soal')->default(1);
                $table->decimal('bobot_per_soal', 5, 2)->default(0);
                $table->timestamps();

                $table->index(['kisi_kisi_soal_id', 'tp_id'], 'kisi_kisi_items_unique_idx');
            });
        }
    }

    public function down(): void
    {
        // Jangan hapus tabel yang mungkin sudah dipakai data produksi.
    }
};
