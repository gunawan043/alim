<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LegerExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        $rows = collect();
        // KKM row
        $kkmRow = collect(['KKM']);
        foreach ($this->data['subjectMap'] as $subject) {
            $book = $this->data['bookMap'][$subject->id] ?? null;
            $kkmRow->push($book?->kktp?->kkm_score ?? '—');
        }
        $kkmRow->push('')->push('')->push('')->push('')->push('')->push('')->push('');
        $rows->push(['type' => 'kkm', 'data' => $kkmRow]);

        // Student rows
        foreach ($this->data['students'] as $idx => $history) {
            $student = $history->student;
            $sid = $history->student_id;
            $avgVal = $this->data['legerAggMap'][$sid] ?? null;
            $rankVal = $this->data['rankMap'][$sid] ?? null;
            $pres = $this->data['presensiMap'][$sid] ?? null;
            $jumlah = 0;
            $count = 0;

            $row = collect();
            $row->push($idx + 1);
            $row->push($student->nis ?? '-');
            $row->push($student->name);

            foreach ($this->data['subjectMap'] as $subject) {
                $book = $this->data['bookMap'][$subject->id] ?? null;
                $n = $book ? ($this->data['nilaiMap'][$sid][$book->id] ?? null) : null;
                if ($n && $n->sts !== null) {
                    $row->push($n->sts);
                    $jumlah += $n->sts;
                    $count++;
                } else {
                    $row->push('—');
                }
            }

            $row->push($count > 0 ? round($jumlah, 1) : '—');
            $row->push($avgVal !== null ? round($avgVal, 1) : '—');
            $row->push($rankVal ?? '—');
            $row->push($this->predikatText($avgVal));
            $row->push($pres['s'] ?? '—');
            $row->push($pres['i'] ?? '—');
            $row->push($pres['a'] ?? '—');

            $rows->push(['type' => 'student', 'data' => $row]);
        }

        return $rows;
    }

    public function headings(): array
    {
        $headers = ['No', 'NIS', 'Nama Santri'];
        foreach ($this->data['subjectMap'] as $subject) {
            $headers[] = $subject->code ?? Str::limit($subject->name, 10);
        }
        $headers = array_merge($headers, ['Jumlah', 'Rata-rata', 'Rank', 'Predikat', 'S', 'I', 'A']);

        return $headers;
    }

    public function map($row): array
    {
        return $row['data']->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        $totalCols = 3 + $this->data['subjectMap']->count() + 7; // No + NIS + Nama + mapel + agg + pres
        $colLetter = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Header row
        $sheet->mergeCells("A1:{$colLetter}1");
        $sheet->setCellValue('A1', 'LEGER NILAI STS — '.strtoupper($this->data['studyGroup']->name).' — TA '.($this->data['selectedAy']?->name ?? '').' SEMESTER '.strtoupper($this->data['selectedSem']));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells("A2:{$colLetter}2");
        $sheet->setCellValue('A2', 'Dicetak: '.now()->translatedFormat('d F Y, H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']],
        ]);

        // Header baris 3
        $headerRow = 3;
        $sheet->getStyle("A{$headerRow}:{$colLetter}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'e0e0e0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->fromArray($this->headings(), null, "A{$headerRow}");

        // KKM row
        $kkmRowIdx = $headerRow + 1;
        $kkmData = $this->collection()->first()['data']->toArray();
        $sheet->fromArray([$kkmData], null, "A{$kkmRowIdx}");
        $sheet->getStyle("A{$kkmRowIdx}:{$colLetter}{$kkmRowIdx}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f0f0f0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Student rows
        $studentStart = $kkmRowIdx + 1;
        foreach ($this->collection()->skip(1) as $rIdx => $row) {
            $excelRow = $studentStart + $rIdx;
            $sheet->fromArray([$row['data']->toArray()], null, "A{$excelRow}");
            $sheet->getStyle("A{$excelRow}:C{$excelRow}")->applyFromArray([
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getStyle("D{$excelRow}:{$colLetter}{$excelRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }
    }

    private function predikatText(?float $avg): string
    {
        if ($avg === null) {
            return '—';
        }
        if ($avg >= 95) {
            return "Mumtaz Murtafi'";
        }
        if ($avg >= 90) {
            return 'Mumtaz';
        }
        if ($avg >= 85) {
            return 'Jayyid Jiddan';
        }
        if ($avg >= 80) {
            return 'Jayyid';
        }
        if ($avg >= 75) {
            return 'Maqbul';
        }

        return 'Roosib';
    }
}
