<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_gtk', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gtk_id')->nullable()->constrained('gtk_profiles')->nullOnDelete();
            $table->date('tanggal');
            $table->string('status', 20)->default('hadir');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->integer('terlambat_menit')->default(0);
            $table->integer('pulang_awal_menit')->default(0);
            $table->text('keterangan')->nullable();
            $table->string('lokasi_masuk')->nullable();
            $table->string('foto_masuk_path')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'gtk_id']);
            $table->index('status');
        });

        Schema::create('absensi_gtk_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_gtk_settings');
        Schema::dropIfExists('absensi_gtk');
    }
};
