<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

/**
 * Template import nama Arab santri — sudah terisi NIS/NISN/nama Latin;
 * operator cukup mengisi kolom `nama_arab`.
 */
class StudentArabicNameTemplateExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly string $schoolId) {}

    public function collection(): Collection
    {
        return Student::query()
            ->where('school_id', $this->schoolId)
            ->orderBy('name')
            ->get(['nis', 'nisn', 'name'])
            ->map(fn (Student $student) => [
                $student->nis,
                $student->nisn,
                $student->name,
                $student->name_ar,
            ]);
    }

    public function headings(): array
    {
        return ['nis', 'nisn', 'nama', 'nama_arab'];
    }
}
