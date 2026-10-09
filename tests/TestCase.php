<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite menyimpan definisi FK lama gtk_employments → positions meskipun
        // tabel `positions` sudah di-merge/di-drop oleh migrasi. Buat stub agar
        // insert fixture ke gtk_employments tidak gagal "no such table: positions".
        if (DB::connection()->getDriverName() === 'sqlite'
            && Schema::hasTable('gtk_employments')
            && ! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        // Migrasi kisi-kisi dilewati pada SQLite (kolom char(36) anonim). Stub
        // minimal agar fitur yang membaca relasi kisi-kisi (paket soal, wizard
        // sumatif) tetap dapat diuji.
        // Stub tabel pivot kisi-kisi & bank soal TP (migrasi aslinya di-skip di SQLite).
        if (DB::connection()->getDriverName() === 'sqlite' && ! Schema::hasTable('kisi_kisi_soal_items')) {
            Schema::create('kisi_kisi_soal_items', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('kisi_kisi_soal_id')->nullable();
                $table->uuid('tp_id')->nullable();
                $table->string('level_kognitif')->nullable();
                $table->integer('jumlah_soal')->nullable();
                $table->decimal('bobot_per_soal', 8, 2)->nullable();
                $table->text('materi')->nullable();
                $table->timestamps();
            });
        }

        if (DB::connection()->getDriverName() === 'sqlite' && ! Schema::hasTable('bank_soal_tp')) {
            Schema::create('bank_soal_tp', function ($table) {
                $table->uuid('bank_soal_id');
                $table->uuid('tp_id');
                $table->timestamps();
            });
        }

        if (DB::connection()->getDriverName() === 'sqlite' && ! Schema::hasTable('kisi_kisi_soal')) {
            Schema::create('kisi_kisi_soal', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('subject_id');
                $table->uuid('grade_level_id');
                $table->uuid('academic_year_id')->nullable();
                $table->uuid('created_by')->nullable();
                $table->string('semester')->nullable();
                $table->string('jenis_ujian')->nullable();
                $table->string('judul')->nullable();
                $table->text('deskripsi')->nullable();
                $table->string('tingkat_sekolah')->nullable();
                $table->string('peminatan')->nullable();
                $table->integer('total_soal_target')->nullable();
                $table->decimal('total_bobot_target', 8, 2)->nullable();
                $table->text('distribusi_kognitif')->nullable();
                $table->text('distribusi_kesulitan')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
}
