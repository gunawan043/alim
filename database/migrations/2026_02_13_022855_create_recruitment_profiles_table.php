<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // Data Pribadi
            $table->text('nik')->nullable();
            $table->text('no_kk')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('nama_ibu_kandung')->nullable();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O'])->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('agama', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])->nullable();
            $table->enum('status_perkawinan', ['belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati'])->default('belum_kawin');
            
            // Kontak
            $table->string('no_hp')->nullable();
            $table->string('no_whatsapp')->nullable();
            $table->string('kontak_darurat')->nullable();
            $table->string('hubungan_kontak_darurat')->nullable(); // relasi keluarga, teman, dll
            
            // Alamat (untuk quick access)
            $table->text('alamat_lengkap')->nullable();
            $table->string('rt_rw')->nullable();
            $table->string('kelurahan_desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota_kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos')->nullable();
            
            // Status Rekrutmen
            $table->enum('status', [
                'draft', 
                'menunggu_verifikasi', 
                'verifikasi_berkas', 
                'lolos_administrasi',
                'tidak_lolos_administrasi',
                'jadwal_tes', 
                'lolos_tes',
                'tidak_lolos_tes',
                'jadwal_wawancara',
                'lolos_wawancara',
                'tidak_lolos_wawancara',
                'pengumuman_akhir',
                'diterima',
                'ditolak',
                'pengunduran_diri'
            ])->default('draft');
            
            // Meta
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('nik');
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_profiles');
    }
};