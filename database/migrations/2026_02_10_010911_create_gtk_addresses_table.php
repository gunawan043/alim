<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gtk_profile_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['ktp', 'domisili']);

            $table->text('jalan');
            $table->string('rt_rw', 10);
            $table->string('dusun')->nullable();
            $table->string('desa');
            $table->string('kode_pos');
            $table->string('kecamatan');
            $table->string('kab_kota');
            $table->string('provinsi');

            // REFERENSI KE CODE (CHAR) - LEBIH UMUM DIGUNAKAN
            $table->char('province_code', 2)->nullable();
            $table->char('city_code', 4)->nullable();
            $table->char('district_code', 7)->nullable();
            $table->char('village_code', 10)->nullable();

            $table->timestamps();

            // Foreign key constraints - referensi ke code
            $table->foreign('province_code')
                ->references('code')
                ->on('indonesia_provinces')
                ->onDelete('set null');

            $table->foreign('city_code')
                ->references('code')
                ->on('indonesia_cities')
                ->onDelete('set null');

            $table->foreign('district_code')
                ->references('code')
                ->on('indonesia_districts')
                ->onDelete('set null');

            $table->foreign('village_code')
                ->references('code')
                ->on('indonesia_villages')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_addresses');
    }
};
