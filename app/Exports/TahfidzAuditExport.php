<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TahfidzAuditExport implements Export, FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $index = 0;

    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['No', 'Kategori Temuan', 'Santri', 'Konteks', 'Keterangan'];
    }

    public function map($row): array
    {
        $this->index++;

        return [
            $this->index,
            $row['category'] ?? '',
            $row['student'] ?? '',
            $row['context'] ?? '',
            $row['detail'] ?? '',
        ];
    }
}
