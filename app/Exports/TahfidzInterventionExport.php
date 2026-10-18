<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TahfidzInterventionExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $index = 0;

    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['No', 'Santri', 'Halaqah', 'Jenis', 'Catatan', 'Batas Waktu', 'Penanggung Jawab', 'Status'];
    }

    public function map($row): array
    {
        $this->index++;

        return [
            $this->index,
            $row->student?->name,
            $row->group?->name,
            $row->type_label,
            $row->notes,
            $row->due_date?->format('d/m/Y'),
            $row->assignedTo?->name,
            $row->status_label,
        ];
    }
}
