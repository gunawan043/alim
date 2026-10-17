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
        Schema::create('gtk_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtk_profile_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['ktp', 'domisili']);

            $table->text('jalan');
            $table->string('rt_rw', 10);
            $table->string('dusun')->nullable();
            $table->string('desa');
            $table->string('kode_pos');
            $table->string('kecamatan');
            $table->string('kab_kota');
            $table->string('provinsi');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_addresses');
    }
};
