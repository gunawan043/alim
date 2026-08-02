<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GtkExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected Collection $records;

    protected int $rowNumber = 0;

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
            'Email',
            'NIK (Masked)',
            'NUPY (Masked)',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Status Perkawinan',
            'No HP (Masked)',
            'Jabatan',
            'Status Kepegawaian',
            'Jenis GTK',
            'TMT',
            'Nomor SK',
            'Satuan Kerja',
            'Jenjang Pendidikan Terakhir',
            'Nama Institusi Pendidikan',
            'Jurusan',
            'Tahun Lulus',
            'Alamat Domisili',
            'Kecamatan',
            'Kab/Kota',
            'Provinsi',
            'Status Akun',
            'Tanggal Daftar',
        ];
    }

    public function map($gtk): array
    {
        $this->rowNumber++;

        $profile = $gtk->gtkProfile;
        $employment = $gtk->employment;
        $contact = $gtk->gtkContact;
        $workUnit = $gtk->gtkWorkUnits?->firstWhere('is_primary', true)?->workUnit;
        $domisili = $profile?->addresses?->firstWhere('type', 'domisili');
        $education = $gtk->educations?->sortByDesc('urutan')->first();

        return [
            $this->rowNumber,
            $gtk->name,
            $gtk->email,
            $profile?->masked_nik ?? '-',
            $employment?->masked_nupy ?? '-',
            $profile?->jenis_kelamin === 'L' ? 'Laki-laki' : ($profile?->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
            $profile?->tempat_lahir ?? '-',
            $profile?->tanggal_lahir?->format('d/m/Y') ?? '-',
            ucfirst($profile?->agama ?? '-'),
            $this->formatPerkawinan($profile?->status_perkawinan),
            $contact?->masked_no_hp ?? '-',
            $employment?->jabatan ?? '-',
            $employment?->status_kepegawaian_text ?? '-',
            $employment?->jenis_gtk ?? '-',
            $employment?->tmt?->format('d/m/Y') ?? '-',
            $employment?->masked_nomor_sk ?? '-',
            $workUnit?->name ?? '-',
            $education?->jenjang_pendidikan_text ?? '-',
            $education?->nama_satuan_pendidikan ?? '-',
            $education?->jurusan ?? '-',
            $education?->tahun_lulus ?? '-',
            $domisili?->jalan ?? '-',
            $domisili?->kecamatan ?? '-',
            $domisili?->kab_kota ?? '-',
            $domisili?->provinsi ?? '-',
            $gtk->is_active ? 'Aktif' : 'Nonaktif',
            $gtk->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowNumber + 1; // +1 untuk header

        return [
            // Header row
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // Semua data rows - border tipis
            "A1:AA{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD1D5DB'],
                    ],
                ],
            ],
        ];
    }

    private function formatPerkawinan(?string $status): string
    {
        return match ($status) {
            'belum_kawin' => 'Belum Kawin',
            'kawin' => 'Kawin',
            'cerai_hidup' => 'Cerai Hidup',
            'cerai_mati' => 'Cerai Mati',
            default => '-',
        };
    }
}
