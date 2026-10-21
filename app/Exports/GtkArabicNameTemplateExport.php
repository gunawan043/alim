<?php

namespace App\Exports;

use App\Models\GtkEmployment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

/**
 * Template import nama Arab GTK — sudah terisi NUPY/nama Latin;
 * operator cukup mengisi kolom `nama_arab`. Pencocokan memakai NUPY.
 */
class GtkArabicNameTemplateExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly ?string $schoolId) {}

    public function collection(): Collection
    {
        return GtkEmployment::query()
            ->with('user:id,name,name_ar')
            ->when($this->schoolId, fn ($q) => $q->where('school_id', $this->schoolId))
            ->whereNotNull('nupy')
            ->orderBy('nupy')
            ->get()
            ->map(fn (GtkEmployment $employment) => [
                $employment->nupy,
                $employment->user?->name,
                $employment->user?->name_ar,
            ]);
    }

    public function headings(): array
    {
        return ['nupy', 'nama', 'nama_arab'];
    }
}
