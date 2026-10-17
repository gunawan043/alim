<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Penomoran surat otomatis per satuan pendidikan dan jenis surat.
 * Format dapat diubah lewat system_settings `surat_format_masuk` / `surat_format_keluar`.
 * Placeholder: {seq} {romawi} {bulan} {tahun}
 */
class SuratNumberService
{
    public function next(string $schoolId, string $type): string
    {
        $year = (int) now()->year;

        $number = DB::transaction(function () use ($schoolId, $type, $year) {
            $row = DB::table('letter_number_sequences')
                ->where('school_id', $schoolId)
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $next = ((int) ($row->last_number ?? 0)) + 1;

            if ($row) {
                DB::table('letter_number_sequences')
                    ->where('id', $row->id)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            } else {
                DB::table('letter_number_sequences')->insert([
                    'id' => (string) Str::uuid(),
                    'school_id' => $schoolId,
                    'type' => $type,
                    'year' => $year,
                    'last_number' => $next,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $next;
        });

        return $this->format($number, $type);
    }

    public function format(int $sequence, string $type): string
    {
        $default = $type === 'masuk'
            ? '{seq}/SM/{romawi}/{tahun}'
            : '{seq}/SK/{romawi}/{tahun}';

        $format = (string) (SystemSetting::get('surat_format_'.$type, $default) ?: $default);

        return str_replace(
            ['{seq}', '{romawi}', '{bulan}', '{tahun}'],
            [
                str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                $this->romanMonth((int) now()->month),
                now()->format('m'),
                (string) now()->year,
            ],
            $format
        );
    }

    private function romanMonth(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1] ?? (string) $month;
    }
}
