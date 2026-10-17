<?php

namespace App\Services\ArabicName;

use App\Imports\ArabicNameRowsImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Membaca spreadsheet nama Arab menjadi array asosiatif berdasarkan header:
 * `nis`, `nisn`, `nupy`, `nama`, `nama_arab` (spasi/strip dinormalisasi).
 */
class ArabicNameSpreadsheetReader
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(UploadedFile $file): array
    {
        $sheets = Excel::toArray(new ArabicNameRowsImport, $file);
        $rows = $sheets[0] ?? [];

        if (empty($rows)) {
            return [];
        }

        $header = array_map(
            fn ($heading) => Str::of((string) $heading)->lower()->trim()->replace([' ', '-', '.'], '_')->toString(),
            $rows[0]
        );

        $output = [];

        foreach (array_slice($rows, 1) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $row[$index] ?? null;
            }

            $joined = implode('', array_map(static fn ($value) => (string) $value, $assoc));
            if (trim($joined) === '') {
                continue;
            }

            $output[] = $assoc;
        }

        return $output;
    }
}
