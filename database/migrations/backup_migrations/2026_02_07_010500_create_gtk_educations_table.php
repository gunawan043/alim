<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gtk_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Data Institusi
            $table->string('jenjang_pendidikan');
            $table->string('nama_sekolah');
            $table->string('jurusan')->nullable();
            $table->string('fakultas')->nullable();
            $table->year('tahun_masuk');
            $table->year('tahun_lulus');
            $table->string('no_ijazah')->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nama_rektor')->nullable();
            
            // Nilai/Indeks
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('skala_nilai', 10)->default('100')->nullable();
            
            // Status & Verification
            $table->boolean('is_aktif')->default(false);
            $table->enum('status', ['LULUS', 'DROPOUT', 'PINDAH', 'BELUM_LULUS'])->default('LULUS');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            
            // File/Document (path)
            $table->string('ijazah_path')->nullable();
            $table->string('transkrip_path')->nullable();
            
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(1); // Untuk sorting pendidikan
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'jenjang_pendidikan']);
            $table->index('is_verified');
            $table->index(['tahun_masuk', 'tahun_lulus']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('gtk_educations');
    }
};