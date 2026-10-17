<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instrumen Supervisi Pembelajaran yang dapat DIATUR (bagian & item + poinnya).
 * Struktur: instrumen → bagian (I, IIA, IIB, IIC) → item (bisa sub-item).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supervisi_instrumen')) {
            Schema::create('supervisi_instrumen', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id')->nullable(); // null = template global (bawaan)
                $table->string('nama', 150);
                $table->string('jenis', 40)->default('guru_umum'); // guru_umum | guru_agama | lainnya
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['school_id', 'jenis']);
            });
        }

        if (! Schema::hasTable('supervisi_instrumen_bagian')) {
            Schema::create('supervisi_instrumen_bagian', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('instrumen_id');
                $table->string('kode', 10)->nullable();   // I, II, IIA, IIB, IIC
                $table->string('nama', 190);
                $table->unsignedInteger('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('instrumen_id')->references('id')->on('supervisi_instrumen')->cascadeOnDelete();
                $table->index(['instrumen_id', 'urutan']);
            });
        }

        if (! Schema::hasTable('supervisi_instrumen_item')) {
            Schema::create('supervisi_instrumen_item', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bagian_id');
                $table->uuid('parent_item_id')->nullable(); // sub-item (a, b, c, ...)
                $table->string('teks', 500);
                $table->unsignedInteger('urutan')->default(0);
                $table->decimal('bobot', 5, 2)->default(4);  // poin maksimum per item (A=4 default)
                $table->boolean('butuh_kondisi')->default(true); // tampilkan kolom Ya/Tidak
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('bagian_id')->references('id')->on('supervisi_instrumen_bagian')->cascadeOnDelete();
                $table->foreign('parent_item_id')->references('id')->on('supervisi_instrumen_item')->nullOnDelete();
                $table->index(['bagian_id', 'urutan']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisi_instrumen_item');
        Schema::dropIfExists('supervisi_instrumen_bagian');
        Schema::dropIfExists('supervisi_instrumen');
    }
};
