<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Marker import untuk membaca spreadsheet menjadi array mentah
 * (dipakai ArabicNameSpreadsheetReader).
 */
class ArabicNameRowsImport implements ToCollection
{
    public function collection(Collection $rows): void
    {
        // Tidak dipakai — pembacaan dilakukan via Excel::toArray().
    }
}
