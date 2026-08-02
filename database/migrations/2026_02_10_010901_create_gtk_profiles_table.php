<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();

            // PERSONAL DATA
            $table->text('nik')->nullable();
            $table->text('no_kk')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('nama_ibu_kandung')->nullable();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O'])->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('agama', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])->nullable();

            // MARITAL STATUS
            $table->enum('status_perkawinan', ['belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati'])->default('belum_kawin');
            $table->text('npwp')->nullable();

            // WORK UNIT RELATION
            $table->foreignUuid('work_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('jabatan')->nullable();
            $table->date('tmt_kerja')->nullable();

            // CONTACT
            $table->string('no_hp')->nullable();
            $table->string('no_whatsapp')->nullable();
            $table->string('kontak_darurat')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_profiles');
    }
};
