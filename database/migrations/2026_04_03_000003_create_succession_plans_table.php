<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('succession_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jabatan_kunci', 191)->comment('Jabatan yang direncanakan suksesinya');
            $table->string('unit_kerja', 191)->nullable();
            $table->foreignUuid('pemegang_jabatan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('perkiraan_kekosongan')->nullable();
            $table->enum('urgensi', ['rendah', 'sedang', 'tinggi', 'kritis'])->default('sedang');
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->text('deskripsi_jabatan')->nullable();
            $table->text('persyaratan_kompetensi')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignUuid('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('succession_plan_kandidat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('succession_plan_id')->constrained('succession_plans')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('kesiapan', ['siap_sekarang', 'siap_1_2_tahun', 'siap_3_5_tahun'])->default('siap_1_2_tahun');
            $table->integer('skor_kesiapan')->nullable()->comment('Skor 0-100');
            $table->integer('prioritas')->default(1)->comment('Urutan prioritas kandidat');
            $table->text('kekuatan')->nullable();
            $table->text('area_pengembangan')->nullable();
            $table->text('rencana_pengembangan')->nullable();
            $table->enum('status', ['aktif', 'dipromosikan', 'dikeluarkan'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('succession_plan_kandidat');
        Schema::dropIfExists('succession_plans');
    }
};
