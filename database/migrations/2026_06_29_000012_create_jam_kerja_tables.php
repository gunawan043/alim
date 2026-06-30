<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jam_kerja', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->boolean('is_active')->default(true);
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->integer('istirahat_menit')->default(60);
            $table->time('istirahat_mulai')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->foreignUuid('jam_kerja_id')->nullable()->constrained('jam_kerja')->nullOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('jam_kerja');
    }
};