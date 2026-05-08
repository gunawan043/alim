<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 21. create_tahfidz_certificates_table.php
// Sertifikat hafalan dari tasmi'an. Nomor sertifikat otomatis, unik.
// Implementasi: Tabel Pengisian Data Nilai Sertifikat Tasmi'an (Gambar 3).
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('academic_year_id');
            $table->uuid('tasmian_participant_id')->unique()->nullable();
            $table->enum('certificate_type', [
                'juz_tunggal', '5_juz', '10_juz',
                '15_juz', '20_juz', '25_juz', '30_juz', 'khatam',
            ]);
            $table->string('certificate_number', 100)->unique()
                  ->comment('Nomor seri sertifikat — generate otomatis');
            $table->date('issued_date');
            $table->integer('total_juz_completed')->nullable();
            $table->json('juz_detail')->nullable()->comment('Daftar juz: [28,29,30]');
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('predikat', 50)->nullable();
            $table->string('golongan', 50)->nullable()
                  ->comment('3 juz / 5 juz / 10 juz — sesuai kategori');
            $table->string('nama_lembaga', 191)->nullable();
            $table->string('kode_lembaga', 50)->nullable();
            $table->string('kode_bulan_tahun_cetak', 30)->nullable()
                  ->comment('Kode Bulan & Tahun Cetak sesuai kolom Gambar 3');
            $table->uuid('issued_by')->nullable();
            $table->date('ceremony_date')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->tinyInteger('is_printed')->default(0);
            $table->timestamp('printed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('tasmian_participant_id')->references('id')->on('tahfidz_tasmian_participants')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->index(
                ['student_id', 'certificate_type'],
                'idx_std_cert'
            );
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_certificates'); }
};
