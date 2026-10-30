<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcademicAnalysisExport implements Export, FromArray, WithStyles
{
    public function __construct(
        private array $analysis,
        private mixed $school,
        private array $filters,
    ) {}

    public function array(): array
    {
        $summary = $this->analysis['summary'];
        $previous = $this->analysis['previous_summary'];
        $comparison = $this->analysis['comparison'];

        $rows = [
            [$this->school?->name ?? config('app.name')],
            ['ANALISIS NILAI MURNI STS/SAS'],
            ['Periode: '.$this->analysis['current_label'].' | Pembanding: '.($this->analysis['previous_label'] ?? '-')],
            [],
            ['RINGKASAN'],
            ['Jumlah data', $summary['total_rows'], 'Santri', $summary['students'], 'Mapel', $summary['subjects'], 'Kelas', $summary['classes']],
            ['Rata-rata STS', $summary['avg_sts'], 'Rata-rata SAS', $summary['avg_sas'], 'Selisih', $summary['avg_delta']],
            ['Median STS', $summary['median_sts'], 'Median SAS', $summary['median_sas'], 'Meningkat', $summary['increased'], 'Menurun', $summary['decreased']],
            ['Tetap', $summary['stable'], 'Nilai belum lengkap', $summary['missing'], 'Di bawah KKTP', $summary['below_kktp']],
            ['Pembanding rata-rata STS', $previous['avg_sts'], 'Pembanding rata-rata SAS', $previous['avg_sas']],
            ['Selisih STS vs pembanding', $comparison['delta_avg_sts'] ?? null, 'Selisih SAS vs pembanding', $comparison['delta_avg_sas'] ?? null],
            [],
            ['TEMUAN OTOMATIS'],
        ];

        foreach ($this->analysis['findings'] as $finding) {
            $rows[] = [$finding['title'], $finding['count'], $finding['description']];
        }

        $rows[] = [];
        $rows[] = ['DETAIL NILAI'];
        $rows[] = [
            'No', 'NISN', 'Nama Santri', 'Kelas', 'Jenjang', 'Mapel', 'Kode',
            'Guru', 'STS', 'SAS', 'Rata-rata', 'Selisih SAS-STS', 'Tren',
            'STS Pembanding', 'SAS Pembanding', 'KKTP', 'Status',
        ];

        foreach ($this->analysis['rows']->values() as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['nisn'] ?: $row['nis'] ?: '-',
                $row['student_name'],
                $row['class_name'],
                $row['grade_level'],
                $row['subject_name'],
                $row['subject_code'],
                $row['teacher_name'],
                $row['sts'],
                $row['sas'],
                $row['average'],
                $row['delta'],
                $this->trendLabel($row['trend']),
                $row['previous_sts'],
                $row['previous_sas'],
                $row['kktp'],
                $row['below_kktp'] ? 'Di bawah KKTP' : ($row['missing'] ? 'Belum lengkap' : 'Terisi'),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('A13')->getFont()->setBold(true);
        $sheet->getStyle('A14')->getFont()->setBold(true);
        $sheet->getStyle('A1:Q1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2:Q2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A3:Q3')->getAlignment()->setHorizontal('center');
        $sheet->freezePane('A15');

        return [];
    }

    private function trendLabel(string $trend): string
    {
        return match ($trend) {
            'increased' => 'Meningkat',
            'decreased' => 'Menurun',
            'stable' => 'Tetap',
            default => 'Belum lengkap',
        };
    }
}
