<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 12. create_tahfidz_setorans_table.php
// TABEL INTI — catatan setoran hafalan harian per santri.
// Implementasi digital Gambar 2: Waraqat al-Mutaba'ah.
// Kolom khofi & jali sesuai kolom penilaian waraqat asli.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_setorans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('teacher_id');
            $table->uuid('tahfidz_group_id');
            $table->uuid('academic_year_id');
            $table->date('setoran_date');
            $table->tinyInteger('week_number')->nullable()->comment('Pekan ke-1 s/d 5 dalam bulan');
            $table->enum('setoran_type', ['ziyadah', 'murajaah', 'tikror'])
                ->comment('ziyadah=hafalan baru, murajaah=pengulangan, tikror=penugasan ulang');
            $table->string('metode_pembelajaran', 100)->nullable()
                ->comment('Talaqqi, Tasmi, Mandiri, Sima\', dst');

            // --- MATERI ---
            $table->smallInteger('surah_start_id')->unsigned()->nullable();
            $table->integer('ayat_start')->nullable();
            $table->smallInteger('surah_end_id')->unsigned()->nullable();
            $table->integer('ayat_end')->nullable();
            $table->tinyInteger('juz')->unsigned()->nullable();
            $table->decimal('halaman_start', 5, 1)->nullable();
            $table->decimal('halaman_end', 5, 1)->nullable();
            $table->decimal('jumlah_halaman', 4, 1)->nullable();
            $table->integer('jumlah_baris')->nullable();

            // --- PENILAIAN WARAQAT (sesuai kolom Gambar 2) ---
            $table->tinyInteger('hasil_hafalan')->nullable()
                ->comment('Nilai hasil hafalan saat setoran: 1-100');
            $table->tinyInteger('khofi')->nullable()
                ->comment('Nilai khafi / dalam hati: 1-100');
            $table->tinyInteger('jali')->nullable()
                ->comment('Nilai jali / keras nyaring: 1-100');
            $table->decimal('nilai_setoran', 5, 2)->nullable()
                ->comment('Nilai akhir setoran ini (rata-rata atau kebijakan guru)');
            $table->enum('capaian_target', ['tercapai', 'belum_tercapai', 'melampaui'])->nullable();
            $table->text('keterangan_capaian')->nullable();

            $table->enum('status', ['lulus', 'ulang', 'ditunda'])->default('lulus');
            $table->text('catatan_guru')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users');
            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('surah_start_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->foreign('surah_end_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->index(['student_id', 'setoran_date', 'setoran_type']);
            $table->index(['tahfidz_group_id', 'setoran_date']);
            $table->index(['student_id', 'academic_year_id', 'juz']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_setorans');
    }
};
