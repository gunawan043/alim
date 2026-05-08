<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recruitment_profile_id')->constrained()->cascadeOnDelete();
            
            // Jenis Dokumen
            $table->enum('jenis_dokumen', [
                'cv', 'surat_lamaran', 'portofolio', 'foto', 
                'ktp', 'kk', 'npwp', 'bpjs', 'akte_kelahiran',
                'ijazah', 'transkrip', 'sertifikat', 'referensi',
                'skck', 'surat_keterangan_sehat', 'surat_keterangan_bebas_narkoba',
                'pas_foto', 'lainnya'
            ]);
            
            $table->string('nama_dokumen');
            $table->string('file_path');
            $table->string('file_size')->nullable(); // Dalam KB/MB
            $table->string('file_extension', 10)->nullable();
            
            // About Me / Resume Text
            $table->text('ringkasan_profesional')->nullable(); // About me
            $table->text('tujuan_karir')->nullable(); // Career objective
            $table->json('keahlian_unggulan')->nullable(); // Highlight skills
            $table->json('pencapaian_utama')->nullable(); // Key achievements
            
            // Status
            $table->boolean('is_public')->default(true);
            $table->boolean('is_primary')->default(false); // CV utama
            $table->integer('version')->default(1); // Versioning dokumen
            
            // Verifikasi
            $table->boolean('is_verified')->default(false);
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('jenis_dokumen');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_documents');
    }
};