<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Default rapor: kop tetap tampil, tetapi TTD KSP, TTD wali, dan stempel
 * dimatikan agar dokumen tidak mencetak gambar tanda tangan secara bawaan.
 * Admin dapat mengaktifkannya lagi lewat switch di halaman Cetak Rapor.
 */
return new class extends Migration
{
    private const FIELDS = ['show_ttd_ksp', 'show_ttd_wali', 'show_stamp'];

    public function up(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            foreach (self::FIELDS as $column) {
                if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->boolean($column)->default(false)->change();
                }
            }
        });

        DB::table('rapor_distribution_settings')->update(array_fill_keys(self::FIELDS, false));

        // Nilai varian tersimpan sebagai JSON; samakan agar preview varian
        // tidak kembali memakai nilai lama.
        DB::table('rapor_distribution_settings')
            ->whereNotNull('variant_layouts')
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                $variants = json_decode((string) $row->variant_layouts, true);

                if (! is_array($variants)) {
                    return;
                }

                $changed = false;
                foreach ($variants as $key => $variant) {
                    if (! is_array($variant)) {
                        continue;
                    }
                    foreach (self::FIELDS as $field) {
                        if (($variant[$field] ?? null) !== false) {
                            $variants[$key][$field] = false;
                            $changed = true;
                        }
                    }
                }

                if ($changed) {
                    DB::table('rapor_distribution_settings')
                        ->where('id', $row->id)
                        ->update(['variant_layouts' => json_encode($variants)]);
                }
            });
    }

    public function down(): void
    {
        // Tidak dikembalikan: pilihan tampil TTD/stempel adalah preferensi admin.
    }
};
