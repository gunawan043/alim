<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill penanda `tahfidz` untuk mapel yang sudah ada.
 *
 * Berdasarkan audit data produksi (2026-11-26): seluruh mapel tahfidz memakai
 * rumpun mapel "Tahfidz" dan/atau nama yang memuat "tahfidz"/"tahfiz"
 * (mis. "Tahfidzul Qur'an", "Tahfizhul Qur'an") dengan kode THF/THF1.
 *
 * Hanya baris yang jelas tahfidz yang ditandai. Pencocokan longgar (mis.
 * "Hafalan Quran") sengaja TIDAK dipakai di sini agar tidak ada mapel lain
 * yang ikut berubah format tanpa keputusan admin. Mapel semacam itu akan
 * muncul sebagai saran pada halaman verifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'penilaian_format')) {
            return;
        }

        DB::table('subjects')->orderBy('id')->each(function ($subject) {
            $name = mb_strtolower((string) ($subject->name ?? ''));
            $code = mb_strtolower(trim((string) ($subject->code ?? '')));
            $group = '';

            if ($subject->subject_group_id) {
                $group = mb_strtolower((string) (DB::table('subject_groups')
                    ->where('id', $subject->subject_group_id)
                    ->value('name') ?? ''));
            }

            $looksTahfidz = str_contains($group, 'tahfidz')
                || str_contains($group, 'tahfiz')
                || str_contains($name, 'tahfidz')
                || str_contains($name, 'tahfiz')
                || preg_match('/^thf\d*$/', $code) === 1;

            if ($looksTahfidz) {
                DB::table('subjects')
                    ->where('id', $subject->id)
                    ->update(['penilaian_format' => 'tahfidz']);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'penilaian_format')) {
            DB::table('subjects')->update(['penilaian_format' => 'umum']);
        }
    }
};
