<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiMonthlySheet implements FromCollection, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $studentRows;

    protected $monthData;

    protected string $rombelName;

    protected string $homeroomName;

    protected string $schoolName;

    protected string $semester;

    protected string $academicYear;

    protected int $month;

    protected int $year;

    protected int $daysInMonth;

    protected Carbon $startDate;

    public function __construct(
        Collection $studentRows,
        $monthData,
        string $rombelName,
        string $homeroomName,
        string $schoolName,
        string $semester,
        string $academicYear,
        int $month,
        int $year,
    ) {
        $this->studentRows = $studentRows;
        $this->monthData = $monthData;
        $this->rombelName = $rombelName;
        $this->homeroomName = $homeroomName;
        $this->schoolName = $schoolName;
        $this->semester = $semester;
        $this->academicYear = $academicYear;
        $this->month = $month;
        $this->year = $year;
        $this->startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $this->daysInMonth = $this->startDate->daysInMonth;
    }

    public function title(): string
    {
        return $this->startDate->locale('id')->monthName.' '.$this->year;
    }

    public function collection(): Collection
    {
        $rows = collect();

        // Baris 1 - 5 (Info)
        $rows->push(['Nama Sekolah', ':', $this->schoolName]);        // Row 1
        $rows->push(['Rombongan Belajar', ':', $this->rombelName]);  // Row 2
        $rows->push(['Wali Kelas', ':', $this->homeroomName]);       // Row 3
        $rows->push(['Tahun Ajaran', ':', $this->academicYear]);     // Row 4
        $rows->push(['Semester', ':', ($this->semester === 'ganjil' ? 'Ganjil' : 'Genap')]); // Row 5

        // Baris 6 (PASTI KOSONG)
        $rows->push(['']); // Row 6: Menggunakan string kosong agar baris tidak di-skip

        // Baris 7 (HEADER)
        $header = ['No', 'NIS', 'Nama Lengkap', 'JK'];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $header[] = $d;
        }
        $header[] = 'S';
        $header[] = 'I';
        $header[] = 'A';
        $rows->push($header); // Row 7

        // Baris 8 (DATA MULAI)
        $idx = 1;
        foreach ($this->studentRows as $student) {
            $totalS = $totalI = $totalA = 0;
            $rowData = [
                $idx,
                $student->nis ?? '-',
                $student->name,
                $student->gender === 'L' ? 'L' : 'P',
            ];

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $dateStr = $this->startDate->copy()->day($d)->toDateString();
                $record = $this->monthData[$student->id][$dateStr] ?? null;

                // Cek apakah record berbentuk array atau object
                $status = is_array($record) ? ($record['status'] ?? null) : ($record->status ?? null);

                $symbol = match ($status) {
                    'hadir' => 'H',
                    'terlambat' => 'T',
                    'izin' => 'I',
                    'sakit' => 'S',
                    'alpa' => 'A',
                    default => '',
                };

                if ($status === 'sakit') {
                    $totalS++;
                } elseif ($status === 'izin') {
                    $totalI++;
                } elseif ($status === 'alpa') {
                    $totalA++;
                }

                $rowData[] = $symbol;
            }

            $rowData[] = $totalS;
            $rowData[] = $totalI;
            $rowData[] = $totalA;

            $rows->push($rowData); // Row 8, 9, 10...
            $idx++;
        }

        // Footer Total
        $rows->push(['']); // Baris kosong pemisah setelah data siswa

        $stats = ['S' => 0, 'I' => 0, 'A' => 0];
        foreach ($this->studentRows as $student) {
            foreach ($this->monthData[$student->id] ?? [] as $dateStr => $record) {
                $s = is_array($record) ? ($record['status'] ?? null) : ($record->status ?? null);
                if ($s === 'sakit') {
                    $stats['S']++;
                } elseif ($s === 'izin') {
                    $stats['I']++;
                } elseif ($s === 'alpa') {
                    $stats['A']++;
                }
            }
        }

        $rows->push(['Total Sakit', ':', $stats['S']]);
        $rows->push(['Total Izin', ':', $stats['I']]);
        $rows->push(['Total Alpa', ':', $stats['A']]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $numStudents = $this->studentRows->count();
        $totalCols = 4 + $this->daysInMonth + 3;
        $lastCol = $this->getColLetter($totalCols);

        $headerRow = 7;
        $dataStart = 8;
        $dataEnd = 8 + $numStudents - 1;
        $footerStart = $dataEnd + 2; // +1 blank separator
        $footerEnd = $footerStart + 2;

        $styles = [];

        // Info label (baris 1-5)
        $styles['A1:B5'] = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E9']],
        ];

        // Header (baris 7)
        $styles["A{$headerRow}:{$lastCol}{$headerRow}"] = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1565C0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // Data siswa (baris 8+)
        $styles["A{$dataStart}:{$lastCol}{$dataEnd}"] = [
            'font' => ['size' => 9],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $styles["A{$dataStart}:A{$dataEnd}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
        $styles["D{$dataStart}:{$lastCol}{$dataEnd}"] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];

        // Footer
        $styles["A{$footerStart}:B{$footerEnd}"] = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        return $styles;
    }

    private function getColLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)).$letter;
            $col = intval($col / 26);
        }

        return $letter;
    }
}
