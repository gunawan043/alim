<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AbsensiDetailExport extends Collection implements FromCollection, WithTitle, WithStyles, ShouldAutoSize
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
        $this->studentRows   = $studentRows;
        $this->monthData     = $monthData;
        $this->rombelName    = $rombelName;
        $this->homeroomName  = $homeroomName;
        $this->schoolName    = $schoolName;
        $this->semester      = $semester;
        $this->academicYear  = $academicYear;
        $this->month         = $month;
        $this->year          = $year;
        $this->startDate     = Carbon::create($year, $month, 1)->startOfMonth();
        $this->daysInMonth   = $this->startDate->daysInMonth;
    }

    public function title(): string
    {
        return $this->startDate->locale('id')->monthName . ' ' . $this->year;
    }

    public function collection(): Collection
    {
        $rows = collect();

        // ── Info block (rows 1-5) ──
        $rows->push(collect(['Nama Sekolah', ':', $this->schoolName]));
        $rows->push(collect(['Rombongan Belajar', ':', $this->rombelName]));
        $rows->push(collect(['Wali Kelas', ':', $this->homeroomName]));
        $rows->push(collect(['Tahun Ajaran', ':', $this->academicYear]));
        $rows->push(collect(['Semester', ':', ($this->semester === 'ganjil' ? 'Ganjil' : 'Genap')]));
        $rows->push(collect([])); // row 6 = blank separator

        // ── Header (row 7) ──
        $header = collect(['No', 'NIS', 'Nama Lengkap', 'JK']);
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $header->push($d);
        }
        $header->push('S');
        $header->push('I');
        $header->push('A');
        $rows->push($header);

        // ── Data rows (starts row 8) ──
        $idx = 1;
        foreach ($this->studentRows as $student) {
            $totalS = $totalI = $totalA = 0;
            $cells = collect([
                $idx,
                $student->nis ?? '-',
                $student->name,
                $student->gender === 'L' ? 'L' : 'P',
            ]);

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $dateStr = $this->startDate->copy()->day($d)->toDateString();
                $record = $this->monthData[$student->id][$dateStr] ?? null;
                $symbol = match ($record?->status) {
                    'hadir'     => 'H',
                    'terlambat' => 'T',
                    'izin'      => 'I',
                    'sakit'     => 'S',
                    'alpa'      => 'A',
                    default     => '',
                };
                if ($record?->status === 'sakit') $totalS++;
                elseif ($record?->status === 'izin') $totalI++;
                elseif ($record?->status === 'alpa') $totalA++;
                $cells->push($symbol);
            }

            $cells->push($totalS);
            $cells->push($totalI);
            $cells->push($totalA);
            $rows->push($cells);
            $idx++;
        }

        // ── Summary footer ──
        $stats = ['S' => 0, 'I' => 0, 'A' => 0];
        foreach ($this->studentRows as $student) {
            foreach ($this->monthData[$student->id] ?? [] as $dateStr => $record) {
                $s = $record?->status;
                if ($s === 'sakit') $stats['S']++;
                elseif ($s === 'izin') $stats['I']++;
                elseif ($s === 'alpa') $stats['A']++;
            }
        }
        $rows->push(collect([]));
        $rows->push(collect(['Total Sakit', ':', $stats['S']]));
        $rows->push(collect(['Total Izin', ':', $stats['I']]));
        $rows->push(collect(['Total Alpa', ':', $stats['A']]));

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $dataCols = 3 + $this->daysInMonth + 3;
        $totalRows = $this->studentRows->count() + 10;

        $lastColLetter = self::colLetter($dataCols);

        $styles = [];

        // ── Info block rows (1-5) ──
        $styles['A1:' . $lastColLetter . '5'] = [
            'font'    => ['size' => 9],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $styles['A1:B5'] = [
            'font'  => ['bold' => true],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // ── Table header row (row 7) ──
        $styles['A7:' . $lastColLetter . '7'] = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill'     => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1565C0']],
            'borders'  => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // ── Data rows (starts row 8) ──
        $dataStart = 8;
        $dataEnd   = $totalRows - 4;
        $styles["A{$dataStart}:A{$dataEnd}"] = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font'     => ['size' => 9],
            'borders'  => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $styles["B{$dataStart}:C{$dataEnd}"] = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'font'     => ['size' => 9],
            'borders'  => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $styles["D{$dataStart}:D{$dataEnd}"] = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font'     => ['size' => 9],
            'borders'  => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $styles["E{$dataStart}:{$lastColLetter}{$dataEnd}"] = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font'     => ['size' => 9],
            'borders'  => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // ── Summary footer ──
        $footerStart = $totalRows - 2;
        $footerEnd   = $totalRows;
        $styles["A{$footerStart}:B{$footerEnd}"] = [
            'font'   => ['bold' => true],
            'fill'   => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $styles["C{$footerStart}:" . $lastColLetter . $footerEnd] = [
            'font'   => ['bold' => true],
            'fill'   => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF9C4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        return $styles;
    }

    private static function colLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = intval($col / 26);
        }
        return $letter;
    }
}
