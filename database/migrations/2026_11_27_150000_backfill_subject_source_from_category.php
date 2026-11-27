<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isi asal mapel di Peta Kurikulum dari Kategori mapel.
 *
 * Kategori mapel (dinas/pondok/dinas_pondok) dan subject_source di Peta
 * menyatakan hal yang sama. Selama Peta belum ditandai, filter leger
 * Pondok/Dinas tampil kosong walau Kategori sudah diatur. Migrasi ini hanya
 * mengisi baris yang masih NULL/kosong; penandaan manual tidak ditimpa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')
            || ! Schema::hasTable('grade_level_subjects')
            || ! Schema::hasColumn('grade_level_subjects', 'subject_source')) {
            return;
        }

        foreach (['dinas' => 'dinas', 'pondok' => 'pondok', 'dinas_pondok' => 'pondok_dinas'] as $category => $source) {
            DB::table('grade_level_subjects')
                ->whereIn('subject_id', function ($q) use ($category) {
                    $q->select('id')->from('subjects')->where('category', $category);
                })
                ->where(fn ($q) => $q->whereNull('subject_source')->orWhere('subject_source', ''))
                ->update(['subject_source' => $source]);
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: nilai kosong tidak bisa dibedakan antara
        // "belum pernah ditandai" dan "sengaja dikosongkan" setelah diisi.
    }
};
