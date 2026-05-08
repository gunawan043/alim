<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gtk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // IDENTITAS PRIBADI (ENCRYPTED VIA CAST)
            $table->text('nik');
            $table->text('no_kk');
            $table->text('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->text('nama_ibu_kandung')->nullable();
            $table->string('golongan_darah', 3)->nullable();
            $table->string('jenis_kelamin')->nullable();

            // STATUS
            $table->string('status_perkawinan');
            $table->text('npwp')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_profiles');
    }
};
