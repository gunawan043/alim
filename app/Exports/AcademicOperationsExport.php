<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcademicOperationsExport implements Export, FromArray, WithStyles
{
    public function __construct(
        private string $title,
        private string $subtitle,
        private array $summaryRows,
        private array $headings,
        private array $rows,
        private ?string $schoolName = null,
    ) {}

    public function array(): array
    {
        $data = [
            [$this->schoolName ?: config('app.name')],
            [$this->title],
            [$this->subtitle],
            [],
        ];

        foreach ($this->summaryRows as $summary) {
            $data[] = $summary;
        }

        $data[] = [];
        $data[] = $this->headings;

        foreach ($this->rows as $row) {
            $data[] = $row;
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $headingRow = 5 + count($this->summaryRows) + 1;

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A'.$headingRow.':'.$sheet->getHighestColumn().$headingRow)->getFont()->setBold(true);

        $sheet->freezePane('A'.($headingRow + 1));

        return [];
    }
}
