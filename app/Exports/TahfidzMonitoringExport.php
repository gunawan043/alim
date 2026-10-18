<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TahfidzMonitoringExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $index = 0;

    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No', 'NIS', 'Nama Santri', 'Halaqah', 'Sekolah', 'Setoran Terakhir',
            'Hari Sejak Setoran', 'Jumlah Setoran', 'Rata Nilai', 'Juz Selesai',
            'Capaian Target (%)', 'Mutaba’ah Terakhir', 'Status', 'Alasan',
        ];
    }

    public function map($row): array
    {
        $this->index++;

        return [
            $this->index,
            $row['student']?->nis,
            $row['student']?->name,
            $row['group']?->name,
            $row['group']?->school?->name,
            $row['last_setoran'] ? Carbon::parse($row['last_setoran'])->format('d/m/Y') : '—',
            $row['setoran_days'] ?? '—',
            $row['setoran_count'],
            $row['avg_nilai'] !== null ? round((float) $row['avg_nilai'], 2) : '—',
            $row['juz_completed'],
            $row['target_percent'] !== null ? round((float) $row['target_percent'], 2) : '—',
            $row['last_mutabaah'] ? Carbon::parse($row['last_mutabaah'])->format('d/m/Y') : '—',
            empty($row['reasons']) ? 'Baik' : 'Perlu Perhatian',
            empty($row['reasons']) ? '' : implode('; ', $row['reasons']),
        ];
    }
}
