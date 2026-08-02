<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_educations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');

            // DATA INSTITUSI
            $table->string('jenjang_pendidikan');
            $table->string('nama_satuan_pendidikan');
            $table->string('jurusan')->nullable();
            $table->string('fakultas')->nullable();
            $table->year('tahun_masuk');
            $table->year('tahun_lulus');
            $table->string('no_ijazah')->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nama_rektor')->nullable();

            // NILAI/INDEKS
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('skala_nilai', 10)->default('100')->nullable();

            // STATUS & VERIFICATION
            $table->boolean('is_aktif')->default(false);
            $table->enum('status', ['LULUS', 'DROPOUT', 'PINDAH', 'BELUM_LULUS'])->default('LULUS');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users');

            // FILE/DOCUMENT
            $table->string('ijazah_path')->nullable();
            $table->string('transkrip_path')->nullable();

            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // INDEXES
            $table->index(['user_id', 'jenjang_pendidikan']);
            $table->index('is_verified');
            $table->index(['tahun_masuk', 'tahun_lulus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_educations');
    }
};
