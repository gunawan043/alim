<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_soal', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('kisi_kisi_soal_id', 36)->index('paket_kisi_idx');
            $table->string('judul', 150);
            $table->string('kode_paket', 50)->unique();
            $table->unsignedInteger('versi')->default(1);
            $table->boolean('is_acak_urutan_soal')->default(true);
            $table->boolean('is_acak_opsi')->default(true);
            $table->unsignedInteger('jumlah_soal_aktual')->default(0);
            $table->decimal('total_bobot_aktual', 6, 2)->default(0);
            $table->unsignedInteger('waktu_pengerjaan_menit')->default(90);
            $table->text('instruksi_umum')->nullable();
            $table->boolean('is_published')->default(false);
            $table->char('published_by', 36)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->enum('shared_scope', ['private', 'internal_school', 'public_pool'])->default('private');
            $table->decimal('kkm', 5, 2)->nullable()->default(null);
            $table->timestamps();
            $table->softDeletes();

            $table->index('shared_scope');
            $table->index('is_published');
        });

        Schema::create('paket_soal_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('paket_soal_id', 36)->index('paket_soal_idx');
            $table->char('soal_id', 36)->index('soal_idx');
            $table->unsignedInteger('urutan')->default(0);
            $table->decimal('bobot_override', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['paket_soal_id', 'soal_id'], 'paket_soal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_soal_items');
        Schema::dropIfExists('paket_soal');
    }
};
