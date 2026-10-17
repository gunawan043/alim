<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('masakerja')) {
            Schema::create('masakerja', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedInteger('masa_kerja')->unique(); // tahun
                $table->unsignedTinyInteger('level');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pendidikan')) {
            Schema::create('pendidikan', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('Strata', 30)->unique();
                $table->unsignedTinyInteger('Golongan'); // 1..5
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('persentase_gaji')) {
            Schema::create('persentase_gaji', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->decimal('persentase_golongan_1', 6, 2)->default(100);
                $table->decimal('persentase_golongan_2', 6, 2)->default(100);
                $table->decimal('persentase_golongan_3', 6, 2)->default(100);
                $table->decimal('persentase_golongan_4', 6, 2)->default(100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salary_scal')) {
            Schema::create('salary_scal', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('pendidikan', 30);
                $table->unsignedInteger('masa_kerja');
                $table->decimal('gaji', 14, 2)->default(0);
                $table->timestamps();

                $table->unique(['pendidikan', 'masa_kerja']);
            });
        }

        foreach (['skala_gaji_guru', 'skala_gaji_tendik'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->unsignedInteger('masa_kerja');
                    $table->unsignedTinyInteger('level');
                    $table->decimal('golongan_1', 14, 2)->default(0);
                    $table->decimal('golongan_2', 14, 2)->default(0);
                    $table->decimal('golongan_3', 14, 2)->default(0);
                    $table->decimal('golongan_4', 14, 2)->default(0);
                    $table->decimal('golongan_5', 14, 2)->default(0);
                    $table->timestamps();

                    $table->unique(['masa_kerja', 'level']);
                });
            }
        }

        if (! Schema::hasTable('tunjangan')) {
            Schema::create('tunjangan', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('Kode', 30)->unique();
                $table->string('Subjek', 191);
                $table->decimal('Nominal', 14, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_attendance_based')->default(false);
                $table->boolean('per_hari')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tunjangan');
        Schema::dropIfExists('skala_gaji_tendik');
        Schema::dropIfExists('skala_gaji_guru');
        Schema::dropIfExists('salary_scal');
        Schema::dropIfExists('persentase_gaji');
        Schema::dropIfExists('pendidikan');
        Schema::dropIfExists('masakerja');
    }
};
