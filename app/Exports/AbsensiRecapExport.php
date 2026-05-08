<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class AbsensiRecapExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected Collection $data;
    protected string $rombelName;
    protected string $monthYear;
    protected string $semester;

    public function __construct(Collection $data, string $rombelName, string $monthYear, string $semester)
    {
        $this->data = $data;
        $this->rombelName = $rombelName;
        $this->monthYear = $monthYear;
        $this->semester = $semester;
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIS',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Total Hadir',
            'Terlambat',
            'Izin',
            'Sakit',
            'Alpa',
            'Total Hari Efektif',
            'Persentase Kehadiran (%)',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        $total = $row['hadir'] + $row['terlambat'] + $row['izin'] + $row['sakit'] + $row['alpa'];
        $hariEfektif = $total;
        $persen = $hariEfektif > 0
            ? round((($row['hadir'] + $row['terlambat']) / $hariEfektif) * 100, 1)
            : 0;

        return [
            $no,
            $row['nis'] ?? '-',
            $row['name'],
            $row['gender'] === 'L' ? 'Laki-laki' : 'Perempuan',
            $row['hadir'],
            $row['terlambat'],
            $row['izin'],
            $row['sakit'],
            $row['alpa'],
            $hariEfektif,
            $persen . '%',
            '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();

        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '2E7D32'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
            'A2:' . $lastCol . '2' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E8EAF6'],
                ],
                'font' => ['bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return "Rekap Absensi {$this->rombelName} - {$this->monthYear}";
    }
}
