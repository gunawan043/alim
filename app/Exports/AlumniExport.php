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

class AlumniExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'NISN',
            'NIK',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Alamat',
            'No. HP',
            'Email',
            'Satuan Pendidikan',
            'Tahun Lulus',
            'No. Ijazah',
            'Status Tracer',
            'Melanjutkan Studi',
            'Kampus / Institution',
            'Jurusan',
            'Tahun Masuk Kuliah',
            'Bekerja',
            'Pekerjaan',
            'Nama Perusahaan',
            'Alamat Perusahaan',
            'Kota Perusahaan',
            'No. Telepon Kantor',
            'Gaji per Bulan',
            'Tahun Mulai Bekerja',
            'Dapat Dihubungi',
            'Prestasi',
            'Catatan',
        ];
    }

    public function map($alumni): array
    {
        static $no = 0;
        $no++;
        $s = $alumni->student;

        return [
            $no,
            $s->name ?? '-',
            $s->nisn ?? '-',
            $s->nik ?? '-',
            $s->gender === 'L' ? 'Laki-laki' : 'Perempuan',
            $s->birth_place ?? '-',
            $s->birth_date?->format('d/m/Y') ?? '-',
            $s->full_address ?? '-',
            $s->mobile_phone ?? '-',
            $s->email ?? '-',
            $alumni->school->name ?? '-',
            $alumni->graduation_year,
            $alumni->graduation_certificate_number ?? '-',
            $alumni->tracer_status_text,
            $alumni->continuing_study_status_text,
            $alumni->higher_education_institution ?? '-',
            $alumni->study_program ?? '-',
            $alumni->higher_education_year_start ?? '-',
            $alumni->working_status_text,
            $alumni->occupation ?? '-',
            $alumni->company_name ?? '-',
            $alumni->company_address ?? '-',
            $alumni->company_city ?? '-',
            $alumni->company_phone ?? '-',
            $alumni->monthly_income ? 'Rp ' . number_format($alumni->monthly_income, 0, ',', '.') : '-',
            $alumni->working_year_start ?? '-',
            $alumni->is_contactable ? 'Ya' : 'Tidak',
            $alumni->achievements ?? '-',
            $alumni->tracer_notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'E9ECEF']],
            ],
        ];
    }
}
