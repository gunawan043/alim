<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JadwalKbmMatrixExport implements Export, FromArray, WithStyles
{
    /** @var array<string, mixed> */
    private array $data;

    private int $headerRow = 0;

    private int $teacherTitleRow = 0;

    private int $teacherHeaderRow = 0;

    private int $teacherLastRow = 0;

    private int $subjectTitleRow = 0;

    private int $subjectHeaderRow = 0;

    private int $subjectLastRow = 0;

    /** @var array<int, int> */
    private array $breakRows = [];

    /** @var array<int, int> */
    private array $dayStartRows = [];

    /** @var array<int, string> */
    private array $conflictCells = [];

    /** @var array<int, string> */
    private array $unavailableCells = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $studyGroups = collect($this->data['studyGroups'] ?? []);
        $matrixData = $this->data['matrixData'] ?? [];
        $days = $matrixData['days'] ?? [];
        $masterDays = $matrixData['masterDays'] ?? [];
        $matrix = $matrixData['matrix'] ?? [];
        $teacherCodes = $matrixData['teacherCodes'] ?? collect();
        $conflictIds = $matrixData['conflictIds'] ?? collect();
        $unavailableIds = $matrixData['unavailableIds'] ?? collect();
        $school = $this->data['school'] ?? null;
        $activeAy = $this->data['activeAy'] ?? null;

        $rows = [];
        $rows[] = [$school?->name ?? config('app.name')];
        $rows[] = ['JADWAL PELAJARAN SELURUH ROMBEL'];
        $rows[] = ['Tahun Ajaran '.($activeAy?->name ?? '-').' ('.ucfirst($activeAy?->semester ?? '-').')'];
        $rows[] = [];

        $classNames = $studyGroups->pluck('name')->all();
        $this->headerRow = count($rows) + 1;
        $rows[] = array_merge(['HARI', 'JAM KE', 'WAKTU'], $classNames);

        foreach ($days as $day => $dayName) {
            $slots = $masterDays[$day] ?? [];
            $period = 0;

            foreach ($slots as $slotIndex => $slot) {
                $isBreak = (bool) ($slot['is_break'] ?? false);
                $excelRow = count($rows) + 1;

                if ($slotIndex === 0) {
                    $this->dayStartRows[] = $excelRow;
                }

                $start = str_replace(':', '.', substr((string) ($slot['start'] ?? ''), 0, 5));
                $end = str_replace(':', '.', substr((string) ($slot['end'] ?? ''), 0, 5));

                $row = [
                    $slotIndex === 0 ? $dayName : '',
                    $isBreak ? '' : ++$period,
                    $start.'–'.$end,
                ];

                if ($isBreak) {
                    $row[] = $slot['label'] ?? 'ISTIRAHAT';
                    for ($i = 1; $i < $studyGroups->count(); $i++) {
                        $row[] = '';
                    }
                    $this->breakRows[] = $excelRow;
                } else {
                    foreach ($studyGroups as $groupIndex => $studyGroup) {
                        $entry = $matrix[$day][$slot['slot']][$studyGroup->id] ?? null;
                        $subjectCode = $entry?->subject?->code ?: ($entry?->subject?->name ?? '');
                        $teacherCode = $entry?->teacher_id
                            ? ($teacherCodes[(string) $entry->teacher_id] ?? '')
                            : '';
                        $row[] = $entry ? trim($subjectCode."\n".$teacherCode) : '';

                        if ($entry && isset($conflictIds[$entry->id])) {
                            $this->conflictCells[] = Coordinate::stringFromColumnIndex(4 + $groupIndex).$excelRow;
                        }
                        if ($entry && isset($unavailableIds[$entry->id])) {
                            $this->unavailableCells[] = Coordinate::stringFromColumnIndex(4 + $groupIndex).$excelRow;
                        }
                    }
                }

                $rows[] = $row;
            }
        }

        $this->teacherTitleRow = count($rows) + 2;
        $rows[] = [];
        $rows[] = ['DAFTAR GURU'];
        $this->teacherHeaderRow = count($rows) + 1;
        $rows[] = ['NO', 'KODE', 'NAMA GURU'];

        foreach (($matrixData['teacherLoads'] ?? collect()) as $load) {
            $rows[] = [$load['no'], $load['code'], $load['teacher']?->name ?? '-'];
        }
        $this->teacherLastRow = count($rows);

        $this->subjectTitleRow = count($rows) + 2;
        $rows[] = [];
        $rows[] = ['KODE MAPEL'];
        $this->subjectHeaderRow = count($rows) + 1;
        $rows[] = ['KODE MAPEL', 'NAMA MAPEL'];

        foreach (($matrixData['subjectCodes'] ?? collect()) as $subject) {
            $rows[] = [$subject['code'], $subject['name']];
        }
        $this->subjectLastRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): ?array
    {
        $lastColumnIndex = max(3, 3 + count($this->data['studyGroups'] ?? []));
        $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$this->headerRow}:{$lastColumn}{$this->headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $firstMatrixRow = $this->headerRow + 1;
        $matrixLastRow = $this->teacherTitleRow - 2;

        if ($matrixLastRow >= $firstMatrixRow) {
            $sheet->getStyle("A{$firstMatrixRow}:{$lastColumn}{$matrixLastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }

        foreach ($this->dayStartRows as $row) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'border' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
        }

        foreach ($this->breakRows as $row) {
            if ($lastColumnIndex >= 4) {
                $sheet->mergeCells("D{$row}:{$lastColumn}{$row}");
            }
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            ]);
        }

        foreach ($this->conflictCells as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            ]);
        }

        foreach ($this->unavailableCells as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            ]);
        }

        $sheet->getStyle("A{$this->teacherTitleRow}:C{$this->teacherTitleRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        $sheet->getStyle("A{$this->teacherHeaderRow}:C{$this->teacherHeaderRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle("A{$this->teacherHeaderRow}:C{$this->teacherLastRow}")->applyFromArray([
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle("A{$this->subjectTitleRow}:B{$this->subjectTitleRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        $sheet->getStyle("A{$this->subjectHeaderRow}:B{$this->subjectHeaderRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle("A{$this->subjectHeaderRow}:B{$this->subjectLastRow}")->applyFromArray([
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(14);

        for ($i = 4; $i <= $lastColumnIndex; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(16);
        }

        for ($row = $firstMatrixRow; $row <= $matrixLastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(28);
        }

        $sheet->freezePane('D'.($this->headerRow + 1));

        return [];
    }
}
