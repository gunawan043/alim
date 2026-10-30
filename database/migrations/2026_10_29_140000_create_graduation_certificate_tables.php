<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat Keterangan Lulus (SKL).
 *
 * - graduation_certificate_settings : format dokumen per sekolah (margin, font,
 *   ukuran kertas, judul, nomor, kop, pejabat penanda tangan, label kelompok).
 * - graduation_certificate_subjects : daftar mata pelajaran yang dicetak beserta
 *   kelompoknya (umum/kejuruan/pilihan/muatan lokal), nama tampilan yang bisa
 *   diubah, urutan, dan status tampil.
 *
 * Kelulusan adalah jalur keluar santri dengan dokumen sendiri (SKL), terpisah
 * dari surat mutasi keluar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('graduation_certificate_settings')) {
            Schema::create('graduation_certificate_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->unique()->constrained('schools')->cascadeOnDelete();

                // Layout cetak
                $table->string('paper_size', 20)->default('a4');
                $table->decimal('margin_top', 5, 2)->default(15);
                $table->decimal('margin_right', 5, 2)->default(15);
                $table->decimal('margin_bottom', 5, 2)->default(15);
                $table->decimal('margin_left', 5, 2)->default(20);
                $table->string('font_family', 60)->default('Times New Roman');
                $table->decimal('font_size', 4, 1)->default(11);
                $table->decimal('line_height', 4, 2)->default(1.30);

                // Identitas dokumen
                $table->boolean('show_kop')->default(true);
                $table->string('title', 100)->default('SURAT KETERANGAN LULUS');
                $table->string('number_prefix', 100)->nullable();
                $table->string('curriculum', 100)->default('Kurikulum Merdeka');
                $table->text('criteria_text')->nullable();

                // Baris data diri yang bisa disembunyikan
                $table->boolean('show_npsn')->default(true);
                $table->boolean('show_ijazah')->default(true);
                $table->boolean('show_nilai')->default(true);
                $table->boolean('show_rata_rata')->default(true);

                // Penanda tangan
                $table->boolean('show_signature')->default(true);
                $table->string('city', 100)->nullable();
                $table->string('signer_jabatan', 100)->default('Kepala Sekolah');
                $table->string('signer_name', 150)->nullable();
                $table->string('signer_nip', 50)->nullable();

                // Label kelompok mapel pada tabel nilai
                $table->json('group_labels')->nullable();

                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('graduation_certificate_subjects')) {
            Schema::create('graduation_certificate_subjects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('group_key', 20)->default('umum');
                $table->string('display_name', 150);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->unique(['school_id', 'subject_id'], 'graduation_cert_subjects_school_subject_unique');
                $table->index(['school_id', 'group_key', 'sort_order'], 'graduation_cert_subjects_order_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_certificate_subjects');
        Schema::dropIfExists('graduation_certificate_settings');
    }
};
