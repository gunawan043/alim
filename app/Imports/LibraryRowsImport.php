<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class LibraryRowsImport implements ToArray
{
    public function array(array $array): void
    {
        // Data dibaca melalui Excel::toArray(); tidak ada proses di sini.
    }
}
