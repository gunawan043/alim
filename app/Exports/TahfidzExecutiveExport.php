<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TahfidzExecutiveExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $index = 0;

    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['No', 'Sekolah', 'Halaqah', 'Santri', 'Setoran Terverifikasi', 'Rata Nilai', 'Juz Selesai', 'Capaian Target (%)', 'Santri Tanpa Setoran 30 Hari', 'Syahadah', 'Tindak Lanjut Berjalan', 'Skor Kesehatan'];
    }

    public function map($row): array
    {
        $this->index++;

        return [
            $this->index,
            $row['school']?->name,
            $row['halaqah'],
            $row['members'],
            $row['verified_setoran'],
            $row['avg_nilai'] !== null ? round($row['avg_nilai'], 2) : '—',
            $row['juz_completed'],
            $row['target_avg'] !== null ? round($row['target_avg'], 2) : '—',
            $row['inactive_members'],
            $row['certificates'],
            $row['open_interventions'],
            $row['health_score'],
        ];
    }
}
