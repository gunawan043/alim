<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiRekapSemesterSheet implements FromCollection, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $studentRows;

    protected $groupedData;

    protected string $rombelName;

    protected string $homeroomName;

    protected string $schoolName;

    protected string $semester;

    protected string $academicYear;

    protected int $year;

    protected array $months;

    public function __construct(
        Collection $studentRows,
        Collection $groupedData,
        string $rombelName,
        string $homeroomName,
        string $schoolName,
        string $semester,
        string $academicYear,
        int $year,
    ) {
        $this->studentRows = $studentRows;
        $this->groupedData = $groupedData;
        $this->rombelName = $rombelName;
        $this->homeroomName = $homeroomName;
        $this->schoolName = $schoolName;
        $this->semester = $semester;
        $this->academicYear = $academicYear;
        $this->year = $year;
        $this->months = $semester === 'ganjil' ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
    }

    public function title(): string
    {
        return 'Rekap Semester';
    }

    public function collection(): Collection
    {
        $rows = collect();

        // ── Info block (rows 1-5) ──
        $rows->push(['Nama Sekolah', ':', $this->schoolName]);
        $rows->push(['Rombongan Belajar', ':', $this->rombelName]);
        $rows->push(['Wali Kelas', ':', $this->homeroomName]);
        $rows->push(['Tahun Ajaran', ':', $this->academicYear]);
        $rows->push(['Semester', ':', ($this->semester === 'ganjil' ? 'Ganjil' : 'Genap')]);
        $rows->push(['']); // row 6 = blank separator

        // ── Header (row 7) ──
        $header = ['No', 'NIS', 'Nama Lengkap', 'JK'];
        foreach ($this->months as $month) {
            $label = \Carbon\Carbon::create($this->year, $month, 1)->locale('id')->monthName;
            $header[] = substr($label, 0, 3);
        }
        $header[] = 'Total Hadir';
        $header[] = 'Total Terlambat';
        $header[] = 'Total Sakit';
        $header[] = 'Total Izin';
        $header[] = 'Total Alpa';
        $header[] = 'Kehadiran (%)';
        $rows->push($header);

        // ── Data rows (starts row 8) ──
        $idx = 1;
        foreach ($this->studentRows as $student) {
            $sid = $student->id;
            $totHadir = $totTerlambat = $totSakit = $totIzin = $totAlpa = 0;
            $cells = [
                $idx,
                $student->nis ?? '-',
                $student->name,
                $student->gender === 'L' ? 'L' : 'P',
            ];

            foreach ($this->months as $month) {
                $monthRecords = collect($this->groupedData[$month][$sid] ?? []);
                $h = $monthRecords->where('status', 'hadir')->count();
                $t = $monthRecords->where('status', 'terlambat')->count();
                $s = $monthRecords->where('status', 'sakit')->count();
                $i = $monthRecords->where('status', 'izin')->count();
                $a = $monthRecords->where('status', 'alpa')->count();
                $totHadir += $h;
                $totTerlambat += $t;
                $totSakit += $s;
                $totIzin += $i;
                $totAlpa += $a;
                $cells[] = $h + $t;
            }

            $total = $totHadir + $totTerlambat + $totSakit + $totIzin + $totAlpa;
            $persen = $total > 0 ? round((($totHadir + $totTerlambat) / $total) * 100, 1) : 0;

            $cells[] = $totHadir;
            $cells[] = $totTerlambat;
            $cells[] = $totSakit;
            $cells[] = $totIzin;
            $cells[] = $totAlpa;
            $cells[] = $persen.'%';
            $rows->push($cells);
            $idx++;
        }

        // ── Summary footer ──
        $grandHadir = $grandTerlambat = $grandSakit = $grandIzin = $grandAlpa = 0;
        foreach ($this->studentRows as $student) {
            $sid = $student->id;
            foreach ($this->months as $month) {
                $monthRecords = collect($this->groupedData[$month][$sid] ?? []);
                $grandHadir += $monthRecords->where('status', 'hadir')->count();
                $grandTerlambat += $monthRecords->where('status', 'terlambat')->count();
                $grandSakit += $monthRecords->where('status', 'sakit')->count();
                $grandIzin += $monthRecords->where('status', 'izin')->count();
                $grandAlpa += $monthRecords->where('status', 'alpa')->count();
            }
        }
        $rows->push(['']); // blank separator
        $rows->push(['Total Keseluruhan', ':', '']);
        $rows->push(['Total Hadir', ':', $grandHadir]);
        $rows->push(['Total Terlambat', ':', $grandTerlambat]);
        $rows->push(['Total Sakit', ':', $grandSakit]);
        $rows->push(['Total Izin', ':', $grandIzin]);
        $rows->push(['Total Alpa', ':', $grandAlpa]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $numStudents = $this->studentRows->count();
        $totalCols = 4 + count($this->months) + 6;
        $lastCol = $this->getColLetter($totalCols);

        $headerRow = 7;
        $dataStart = 8;
        $dataEnd = 8 + $numStudents - 1;
        $footerStart = $dataEnd + 2;
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
