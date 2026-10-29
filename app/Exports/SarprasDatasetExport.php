<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SarprasDatasetExport implements Export, FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title = 'Data Sarpras',
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return array_values($this->headings);
    }

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }
}
