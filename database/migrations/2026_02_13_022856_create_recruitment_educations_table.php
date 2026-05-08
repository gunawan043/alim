<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_educations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recruitment_profile_id')->constrained()->cascadeOnDelete();
            
            // Jenjang Pendidikan
            $table->enum('jenjang', [
                'sd', 'smp', 'sma', 'smk', 
                'd1', 'd2', 'd3', 'd4', 
                's1', 's2', 's3', 
                'profesi', 'spesialis'
            ]);
            
            $table->string('nama_sekolah');
            $table->string('jurusan')->nullable();
            $table->string('fakultas')->nullable();
            
            // Tahun
            $table->year('tahun_masuk');
            $table->year('tahun_lulus');
            $table->boolean('is_ijazah_ada')->default(true);
            $table->string('no_ijazah')->nullable();
            
            // Nilai
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('skala_nilai', 10)->default('100')->nullable();
            $table->decimal('ipk', 4, 2)->nullable(); // Untuk jenjang D3/S1/S2/S3
            $table->string('predikat_kelulusan')->nullable(); // Cumlaude, Sangat Memuaskan, Memuaskan
            
            // File Upload
            $table->string('ijazah_path')->nullable();
            $table->string('transkrip_path')->nullable();
            $table->string('sertifikat_akreditasi_path')->nullable();
            
            // Status Verifikasi
            $table->boolean('is_verified')->default(false);
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('jenjang');
            $table->index('tahun_lulus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_educations');
    }
};