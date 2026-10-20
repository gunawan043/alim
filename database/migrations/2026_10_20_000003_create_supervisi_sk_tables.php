<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supervisi_sk')) {
            Schema::create('supervisi_sk', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->string('nomor', 100);
                $table->date('tanggal');
                $table->text('menimbang')->nullable();
                $table->text('mengingat')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'academic_year_id', 'semester'], 'supervisi_sk_periode_unique');
            });
        }

        if (! Schema::hasTable('supervisi_tim_anggota')) {
            Schema::create('supervisi_tim_anggota', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('supervisi_sk_id')->constrained('supervisi_sk')->cascadeOnDelete();
                $table->foreignUuid('gtk_profile_id')->constrained('gtk_profiles')->cascadeOnDelete();
                $table->string('peran', 40)->default('guru');
                $table->unsignedSmallInteger('urutan')->default(0);
                $table->timestamps();

                $table->unique(['supervisi_sk_id', 'gtk_profile_id'], 'supervisi_tim_anggota_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisi_tim_anggota');
        Schema::dropIfExists('supervisi_sk');
    }
};
