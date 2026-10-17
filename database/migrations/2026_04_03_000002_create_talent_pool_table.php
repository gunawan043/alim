<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_pool', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('kategori', ['high_potential', 'high_performer', 'key_talent', 'emerging_talent'])->default('high_potential');
            $table->enum('status', ['aktif', 'tidak_aktif', 'dipromosikan', 'keluar'])->default('aktif');
            $table->integer('skor_potensi')->nullable()->comment('Skor 0-100');
            $table->integer('skor_kinerja')->nullable()->comment('Skor 0-100');
            $table->text('kompetensi_unggulan')->nullable();
            $table->text('area_pengembangan')->nullable();
            $table->string('jabatan_target', 191)->nullable();
            $table->integer('estimasi_siap_tahun')->nullable()->comment('Berapa tahun lagi siap');
            $table->date('tanggal_masuk_pool');
            $table->date('tanggal_keluar_pool')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignUuid('dinominasikan_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_pool');
    }
};
