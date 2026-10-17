<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_analysis', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('soal_id', 36);
            $table->char('paket_soal_id', 36)->nullable();
            $table->char('academic_year_id', 36);
            $table->enum('semester', ['ganjil', 'genap']);
            $table->enum('jenis_ujian', ['sts', 'sas', 'ulangan_harian', 'try_out', 'latihan']);
            $table->unsignedTinyInteger('total_disertai')->default(0)->comment('Jumlah siswa yang mengerjakan soal ini');
            $table->unsignedTinyInteger('jumlah_jawab_benar')->default(0)->comment('Jumlah siswa yang menjawab benar');
            $table->unsignedTinyInteger('jumlah_jawab_salah')->default(0);
            $table->decimal('index_kesulitan', 3, 2)->nullable()->comment('p-value: 0=easy, 1=hard');
            $table->enum('kategori_kesulitan', ['sulit', 'sedang', 'mudah'])->nullable();
            $table->decimal('taraf_pembedaan', 3, 2)->nullable()->comment('diff_index: 0-1 higher=better');
            $table->enum('kategori_pembedaan', ['baik Sekali', 'baik', 'kurang', 'gagal'])->nullable();
            $table->char('option_terpilih_a', 36)->nullable();
            $table->integer('pilihan_a')->default(0)->comment('Count of students choosing option A');
            $table->integer('pilihan_b')->default(0);
            $table->integer('pilihan_c')->default(0);
            $table->integer('pilihan_d')->default(0);
            $table->integer('pilihan_e')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('soal_id')->references('id')->on('soal')->onDelete('cascade');
            $table->foreign('paket_soal_id')->references('id')->on('paket_soal')->onDelete('set null');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');

            $table->unique(['soal_id', 'paket_soal_id', 'academic_year_id', 'jenis_ujian'], 'item_analysis_unique_soal_context');
            $table->index(['soal_id', 'computed_at'], 'item_analysis_idx_soal_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_analysis');
    }
};
