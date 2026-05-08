<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('jenis_gtk_id');
            $table->string('nama', 150);
            $table->string('kategori', 50)->nullable(); // contoh: 'akademik', 'administratif', 'kependidikan'
            $table->string('deskripsi', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();

            $table->foreign('jenis_gtk_id')
                ->references('id')
                ->on('jenis_gtk')
                ->onDelete('cascade');

            $table->unique(['jenis_gtk_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan');
    }
};
