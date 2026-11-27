<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Samakan kategori mapel dengan penandaan di Peta Kurikulum.
 *
 * Pemetaan kategori awal (nasional → dinas) menandai mapel pondok sebagai
 * dinas. Peta Kurikulum menunjukkan sebaliknya: mapel ini memang mapel
 * pondok. Migrasi hanya mengubah baris yang kategorinya masih 'dinas',
 * sehingga koreksi manual yang sudah dilakukan admin tidak ditimpa.
 *
 * Termasuk dua ejaan yang dipakai bergantian (Tahfidzul/Tahfizhul Qur'an,
 * Fikih/Fiqih) karena masing-masing tercatat sebagai mapel terpisah.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $mapelPondok = [
        'Aqidah',
        'Bahasa Arab',
        'Fikih',
        'Fiqih',
        'Hafalan Hadits',
        'Kitabah',
        'Khot',
        'Liqo Maftuh (Upacara)',
        'Pembiasaan',
        'Qowaid Lughoh',
        "Ta'bir",
        'Tahfidzul Qur\'an',
        'Tahfizhul Qur\'an',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'category')) {
            return;
        }

        DB::table('subjects')
            ->whereIn('name', $this->mapelPondok)
            ->where('category', 'dinas')
            ->update(['category' => 'pondok']);
    }

    public function down(): void
    {
        // Tidak dikembalikan: kategori 'pondok' juga berasal dari data lama
        // (lokal/muatan_lokal) sehingga tidak bisa dibedakan per baris.
    }
};
