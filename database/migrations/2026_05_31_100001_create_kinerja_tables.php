<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Komponen penilaian master (Disiplin, Kehadiran, Administrasi, dll)
        Schema::create('kinerja_komponen', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');              // Disiplin, Kehadiran, Administrasi, dll
            $table->text('deskripsi')->nullable();
            $table->decimal('bobot_persen', 5, 2)->default(0);  // bobot terhadap total
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Periode penilaian (tahunan/semester)
        Schema::create('kinerja_periode', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama'); // "2026 - Semester 1"
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Indikator penilaian per komponen
        Schema::create('kinerja_indikator', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kinerja_komponen_id')->constrained('kinerja_komponen')->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('bobot_persen', 5, 2)->default(0);
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Penilaian kinerja per GTK
        Schema::create('kinerja_penilaian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('kinerja_periode_id')->constrained('kinerja_periode')->cascadeOnDelete();
            $table->foreignUuid('penilai_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->decimal('total_skor', 6, 2)->nullable();
            $table->string('nilai_huruf')->nullable();           // A, B, C, D
            $table->string('kategori_hasil')->nullable();       // Sangat Baik, Baik, Cukup, Kurang
            $table->text('catatan_penilai')->nullable();
            $table->text('catatan_rekonsiliasi')->nullable();
            $table->enum('status', ['draft', 'dinilai', 'rekon', 'final'])->default('draft');
            $table->timestamp('tanggal_penilaian')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kinerja_periode_id'], 'penilaian_unique');
            $table->index('user_id');
            $table->index('kinerja_periode_id');
        });

        // Detail skor per indikator
        Schema::create('kinerja_skor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kinerja_penilaian_id')->constrained('kinerja_penilaian')->cascadeOnDelete();
            $table->foreignUuid('kinerja_indikator_id')->constrained('kinerja_indikator')->cascadeOnDelete();
            $table->decimal('skor', 6, 2); // nilai 0-100
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['kinerja_penilaian_id', 'kinerja_indikator_id'], 'skor_unique');
        });

        // Reward& Punishment
        Schema::create('kinerja_reward_punishment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('kinerja_periode_id')->nullable()->constrained('kinerja_periode')->cascadeOnDelete();
            $table->enum('jenis', ['reward', 'punishment']);
            $table->string('kategori'); // Penghargaan, Bonus, Teguran, Surat Peringatan
            $table->string('nama'); // "GTK Teladan", "Surat Peringatan1"
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->foreignUuid('diberikan_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->text('dokumen_path')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kinerja_skor');
        Schema::dropIfExists('kinerja_penilaian');
        Schema::dropIfExists('kinerja_indikator');
        Schema::dropIfExists('kinerja_periode');
        Schema::dropIfExists('kinerja_komponen');
        Schema::dropIfExists('kinerja_reward_punishment');
    }
};
