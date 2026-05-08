<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recruitment_profile_id')->constrained()->cascadeOnDelete();
            
            // Kategori Skill
            $table->enum('kategori', [
                'teknis', 'non_teknis', 'bahasa', 'sertifikasi', 'lainnya'
            ]);
            
            $table->string('nama_skill');
            $table->string('level', 50)->nullable(); // Pemula, Menengah, Ahli, dll
            $table->integer('tahun_pengalaman')->nullable();
            $table->enum('sumber', [
                'pendidikan', 'kursus', 'otodidak', 'pengalaman_kerja', 'sertifikasi'
            ])->nullable();
            
            // Untuk Skill Bahasa
            $table->enum('kemampuan_lisan', ['pasif', 'aktif', 'native'])->nullable();
            $table->enum('kemampuan_menulis', ['pasif', 'aktif', 'native'])->nullable();
            
            // Sertifikasi terkait skill
            $table->string('sertifikasi_path')->nullable();
            $table->date('tanggal_sertifikasi')->nullable();
            $table->date('berlaku_sampai')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('kategori');
            $table->index('nama_skill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_skills');
    }
};