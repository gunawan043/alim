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
        Schema::create('surat_masuk', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->date('tanggal_diterima');
            $table->string('pengirim');
            $table->string('perihal');
            $table->string('file_lampiran')->nullable();
            $table->string('sifat')->default('biasa'); // rahasia | biasa | penting
            $table->string('sifat_penyelesaian')->default('biasa'); // segera | biasa
            $table->string('disposisi_to')->nullable();
            $table->text('disposisi_catatan')->nullable();
            $table->string('status')->default('baru'); // baru | didisposisi | selesai
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'tanggal_diterima'], 'idx_sm_tgl_terima');
            $table->index(['school_id', 'status'], 'idx_sm_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_masuk');
    }
};
