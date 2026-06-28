<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bank_soal_id');
            $table->uuid('tp_id')->nullable();
            $table->enum('tipe_soal', [
                'pg',
                'mcc',
                'bs',
                'jodoh',
                'isian',
                'uraian',
            ]);
            $table->longText('pertanyaan');
            $table->string('gambar_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->decimal('bobot_default', 5, 2)->default(1.00);
            $table->enum('tingkat_kesulitan_estimasi', ['mudah', 'sedang', 'sulit'])->default('sedang');
            $table->unsignedInteger('waktu_estimasi_menit')->default(2);
            $table->enum('status', ['draft', 'review', 'approved', 'archived'])->default('draft');
            $table->uuid('dibuat_oleh')->nullable();
            $table->uuid('direview_oleh')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('tags')->nullable();
            $table->string('content_hash', 64);
            $table->json('shingles_hash')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->onDelete('cascade');
            $table->foreign('tp_id')->references('id')->on('tujuan_pembelajaran')->nullOnDelete();
            $table->foreign('dibuat_oleh')->references('id')->on('users')->nullOnDelete();
            $table->foreign('direview_oleh')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['bank_soal_id', 'tipe_soal'], 'soal_bank_tipe_idx');
            $table->index('tp_id', 'soal_tp_idx');
            $table->index('status', 'soal_status_idx');
        });

        // Add the unique content_hash constraint using Schema::table so it
        // works on both MySQL and SQLite. SQLite doesn't support named
        // unique indexes the same way MySQL does, so we skip the named
        // index there and rely on the implicit unique constraint name.
        if (! \Illuminate\Support\Facades\Schema::hasIndex('soal', 'soal_content_hash_unique')) {
            \Illuminate\Support\Facades\Schema::table('soal', function ($table) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $table->unique('content_hash');
                } else {
                    $table->unique('content_hash', 'soal_content_hash_unique');
                }
            });
        }

        Schema::create('soal_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('soal_id');
            $table->string('label', 10);
            $table->text('teks_opsi');
            $table->string('gambar_path')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->foreign('soal_id')->references('id')->on('soal')->onDelete('cascade');
            $table->index(['soal_id', 'urutan'], 'soal_options_soal_urutan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal_options');
        Schema::dropIfExists('soal');
    }
};
