<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AbsensiSemesterFullExport implements WithMultipleSheets
{
    public function __construct(
        public Collection $studentRows,
        public Collection $groupedData,
        public string $rombelName,
        public string $homeroomName,
        public string $schoolName,
        public string $semester,
        public string $academicYear,
        public int $year,
    ) {}

    public function sheets(): array
    {
        $months = $this->semester === 'ganjil'
            ? [7, 8, 9, 10, 11, 12]
            : [1, 2, 3, 4, 5, 6];

        $sheets = [];
        foreach ($months as $month) {
            $sheets[] = new AbsensiMonthlySheet(
                studentRows:    $this->studentRows,
                monthData:      $this->groupedData[$month] ?? collect(),
                rombelName:     $this->rombelName,
                homeroomName:   $this->homeroomName,
                schoolName:     $this->schoolName,
                semester:       $this->semester,
                academicYear:   $this->academicYear,
                month:          $month,
                year:           $this->year,
            );
        }

        // Sheet ke-7: Rekap Semester
        $sheets[] = new AbsensiRekapSemesterSheet(
            studentRows:    $this->studentRows,
            groupedData:    $this->groupedData,
            rombelName:     $this->rombelName,
            homeroomName:   $this->homeroomName,
            schoolName:     $this->schoolName,
            semester:       $this->semester,
            academicYear:   $this->academicYear,
            year:           $this->year,
        );

        return $sheets;
    }
}
