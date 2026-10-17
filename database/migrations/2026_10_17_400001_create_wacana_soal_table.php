<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wacana / soal cerita (bacaan bersama untuk beberapa nomor).
 *
 * Satu wacana dapat dipakai banyak soal; saat paket dibekukan (snapshot),
 * judul & teks wacana ikut disalin ke `paket_soal_items.konten_snapshot`
 * sehingga lembar soal tetap sama meski soal diedit kemudian.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wacana_soal')) {
            Schema::create('wacana_soal', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bank_soal_id')->nullable();
                $table->uuid('school_id')->nullable();
                $table->string('judul', 150)->nullable();
                $table->longText('teks');
                $table->string('sumber', 150)->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->nullOnDelete();
                $table->index('bank_soal_id', 'wacana_soal_bank_idx');
            });
        }

        if (Schema::hasTable('soal') && ! Schema::hasColumn('soal', 'wacana_id')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->uuid('wacana_id')->nullable()->after('tp_id');
                $table->index('wacana_id', 'soal_wacana_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('soal') && Schema::hasColumn('soal', 'wacana_id')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->dropIndex('soal_wacana_idx');
                $table->dropColumn('wacana_id');
            });
        }

        Schema::dropIfExists('wacana_soal');
    }
};
