<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isi awal kolom `kelompok` berdasarkan nama mapel yang sudah ada.
 * Nama-nama yang tidak dikenali tetap di kelompok 'umum'.
 */
return new class extends Migration
{
    private const AGAMA = [
        'fikih', 'fiqih', 'ushul fikih', 'usul fikih', 'usul fiqh', 'ushul fiqh',
        'aqidah', 'akidah', 'adab', 'tahfidz', 'tahfidzul', 'qur\'an', 'al-qur\'an',
        'quran', 'hadits', 'hadist', 'sirah', 'tarikh', 'tauhid', 'akhlak',
        'qowaid', 'balaghah', 'balagoh', 'sharaf', 'nahwu', 'manhaj', 'faraid',
        'mentoring keislaman', 'bimbingan keislaman', 'pendidikan agama',
    ];

    private const BAHASA = [
        'bahasa arab', 'b. arab', 'b.arab', 'bahasa indonesia', 'bahasa inggris',
        'b. inggris', 'b.inggris', 'imla', 'insya', 'muhadatsah', 'khot', 'qiroah',
        'ta\'bir', 'tarkib', 'kitabah',
    ];

    private const BAHASA_TOKOH = [
        'nabi ', 'sahabat', 'kisah', 'sirah nabawiyah',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'kelompok')) {
            return;
        }

        // 1) Rumpun resmi (subject_groups) → kelompok.
        $rumpunMap = ['agama' => 'agama', 'bahasa' => 'bahasa', 'tahfidz' => 'agama', 'umum' => 'umum'];

        foreach ($rumpunMap as $rumpun => $kelompok) {
            $groupIds = DB::table('subject_groups')
                ->whereRaw('LOWER(name) = ?', [$rumpun])
                ->pluck('id');

            if ($groupIds->isNotEmpty()) {
                DB::table('subjects')
                    ->whereIn('subject_group_id', $groupIds)
                    ->update(['kelompok' => $kelompok]);
            }
        }

        // 2) Mapel tanpa rumpun → tebak dari nama.
        DB::table('subjects')->whereNull('subject_group_id')->select('id', 'name')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('subjects')
                    ->where('id', $row->id)
                    ->update(['kelompok' => self::classify($row->name)]);
            }
        });
    }

    public function down(): void
    {
        // Data kategori tidak dikembalikan ke tebakan nama.
    }

    private static function classify(?string $name): string
    {
        $name = strtolower(trim((string) $name));

        if ($name === '') {
            return 'umum';
        }

        // Tokoh/istilah Bahasa Indonesia (Nabi, Sahabat, dsb.) bukan mapel bahasa.
        foreach (self::BAHASA_TOKOH as $needle) {
            if (str_contains($name, $needle)) {
                return 'umum';
            }
        }

        foreach (self::BAHASA as $needle) {
            if (str_contains($name, $needle)) {
                return 'bahasa';
            }
        }

        foreach (self::AGAMA as $needle) {
            if (str_contains($name, $needle)) {
                return 'agama';
            }
        }

        return 'umum';
    }
};
