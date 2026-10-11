<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Increment 3 — penyelarasan master rumpun dengan Kebijakan Akademik:
 *  - Empat rumpun resmi: Umum, Agama, Bahasa, Tahfidz.
 *  - Rumpun "Hadits" dilebur ke Agama (Akidah/Hadits/Fikih = mapel agama).
 *  - "Bahasa Arab" menjadi "Bahasa".
 *  - Nama tugas tambahan koordinator mengikuti empat nama resmi yang ada.
 *  - `patterns` dipertahankan hanya sebagai SARAN pemetaan di UI
 *    (otoritas rumpun tetap `subjects.subject_group_id`).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $official = [
            'tahfidz' => [
                'name' => 'Tahfidz',
                'coordinator_task_name' => 'Koordinator Guru Tahfidz',
                'patterns' => ['tahfidz', 'tahfiz', 'hifzh', 'hafalan', 'quran', "qur'an", 'tajwid'],
                'sort_order' => 10,
            ],
            'bahasa' => [
                'name' => 'Bahasa',
                'coordinator_task_name' => 'Koordinator Guru Bahasa',
                'patterns' => ['bahasa arab', 'b. arab', 'b.arab', 'arab', 'qowaid', 'sharaf', 'tashrif', 'imla', 'muthalaah', 'insya', 'balaghah', 'bahasa inggris', 'english'],
                'sort_order' => 20,
            ],
            'agama' => [
                'name' => 'Agama',
                'coordinator_task_name' => 'Koordinator Guru Agama',
                'patterns' => ['aqidah', 'akidah', 'adab', 'fiqih', 'fikih', 'hadits', 'hadist', 'tarikh', 'ski', 'pendidikan agama', 'pai'],
                'sort_order' => 30,
            ],
            'umum' => [
                'name' => 'Umum',
                'coordinator_task_name' => 'Koordinator Guru Umum',
                'patterns' => ['*'],
                'sort_order' => 999,
            ],
        ];

        // 1) Rename "bahasa_arab" → "bahasa" bila belum ada baris "bahasa".
        $bahasaLama = DB::table('subject_groups')->where('code', 'bahasa_arab')->first();
        $bahasaBaru = DB::table('subject_groups')->where('code', 'bahasa')->first();

        if ($bahasaLama && ! $bahasaBaru) {
            DB::table('subject_groups')->where('id', $bahasaLama->id)->update(['code' => 'bahasa', 'updated_at' => $now]);
        } elseif ($bahasaLama && $bahasaBaru) {
            DB::table('subjects')->where('subject_group_id', $bahasaLama->id)->update(['subject_group_id' => $bahasaBaru->id]);
            DB::table('subject_groups')->where('id', $bahasaLama->id)->delete();
        }

        // 2) Rumpun "hadits" dilebur ke "agama" lalu dihapus.
        $hadits = DB::table('subject_groups')->where('code', 'hadits')->first();
        $agama = DB::table('subject_groups')->where('code', 'agama')->first();

        if ($hadits && $agama) {
            DB::table('subjects')->where('subject_group_id', $hadits->id)->update(['subject_group_id' => $agama->id]);
            DB::table('subject_groups')->where('id', $hadits->id)->delete();
        } elseif ($hadits && ! $agama) {
            DB::table('subject_groups')->where('id', $hadits->id)->update(['code' => 'agama', 'updated_at' => $now]);
        }

        // 3) Pastikan empat rumpun resmi ada + perbarui nama tugas koordinator & pola saran.
        foreach ($official as $code => $meta) {
            $existing = DB::table('subject_groups')->where('code', $code)->first();

            if ($existing) {
                DB::table('subject_groups')->where('id', $existing->id)->update([
                    'name' => $meta['name'],
                    'coordinator_task_name' => $meta['coordinator_task_name'],
                    'patterns' => json_encode($meta['patterns']),
                    'sort_order' => $meta['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('subject_groups')->insert([
                    'id' => (string) Illuminate\Support\Str::uuid(),
                    'code' => $code,
                    'name' => $meta['name'],
                    'coordinator_task_name' => $meta['coordinator_task_name'],
                    'patterns' => json_encode($meta['patterns']),
                    'sort_order' => $meta['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan penggabungan rumpun (data historis tetap valid).
    }
};
