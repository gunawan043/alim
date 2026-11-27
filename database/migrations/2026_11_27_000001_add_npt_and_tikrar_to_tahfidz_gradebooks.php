<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NPT dan Tikrar menjadi kolom nilai penuh pada format tahfidz.
 *
 * Sebelumnya keduanya hanya angka pendamping yang dihitung di leger
 * (NPT dari capaian target, Tikrar dari rata-rata setoran) sehingga tidak
 * bisa dibobotkan ke NR Final dan tidak bisa dikoreksi guru. Sekarang
 * keduanya disimpan di buku halaqah: nilai awal tetap ditarik otomatis
 * dari target/setoran, tetapi guru dapat menimpanya.
 *
 * Bobot dibagi rata (25% tiap kolom) hanya untuk baris yang dibuat
 * otomatis setelah migrasi ini; buku lama memakai bobotnya sendiri dengan
 * NPT dan Tikrar berbobot 0 agar NR Final yang sudah tercatat tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahfidz_gradebooks', function (Blueprint $table) {
            if (! Schema::hasColumn('tahfidz_gradebooks', 'weight_npt')) {
                $table->decimal('weight_npt', 4, 1)->default(0);
            }
            if (! Schema::hasColumn('tahfidz_gradebooks', 'weight_tikrar')) {
                $table->decimal('weight_tikrar', 4, 1)->default(0);
            }
        });

        Schema::table('tahfidz_gradebook_scores', function (Blueprint $table) {
            if (! Schema::hasColumn('tahfidz_gradebook_scores', 'npt')) {
                $table->decimal('npt', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('tahfidz_gradebook_scores', 'tikrar')) {
                $table->decimal('tikrar', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tahfidz_gradebook_scores', function (Blueprint $table) {
            $table->dropColumn(['npt', 'tikrar']);
        });

        Schema::table('tahfidz_gradebooks', function (Blueprint $table) {
            $table->dropColumn(['weight_npt', 'weight_tikrar']);
        });
    }
};
