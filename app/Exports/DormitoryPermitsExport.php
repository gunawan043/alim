<?php

namespace App\Exports;

use App\Models\Dormitory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Laporan perizinan (bulk) — Excel rapi & terstruktur per asrama/periode.
 */
class DormitoryPermitsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $nomor = 0;

    public function __construct(
        private readonly Collection $records,
        private readonly Dormitory $dormitory,
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function title(): string
    {
        return 'Perizinan';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Tanggal Pengajuan',
            'NISN',
            'Nama Santri',
            'Kamar',
            'Jenis Izin',
            'Kategori',
            'Tujuan',
            'Perihal',
            'Berangkat',
            'Estimasi Kembali',
            'Kepulangan Aktual',
            'Penjemput',
            'Mode Penjemputan',
            'Diverifikasi QR',
            'Waktu QR Discan',
            'Status',
            'Emergency Contact',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($permit): array
    {
        $this->nomor++;

        return [
            $this->nomor,
            $permit->created_at?->format('Y-m-d') ?? '-',
            $permit->student?->nisn ?? '-',
            $permit->student?->name ?? '-',
            $permit->room?->code ?? $permit->room?->name ?? '-',
            $permit->permit_type ?? '-',
            $permit->category ?? '-',
            $permit->destination ?? '-',
            $permit->purpose ?? '-',
            $permit->departure_datetime?->format('Y-m-d H:i') ?? '-',
            $permit->expected_return_datetime?->format('Y-m-d H:i') ?? '-',
            $permit->actual_return_datetime?->format('Y-m-d H:i') ?? '-',
            $permit->companion_name ?? '-',
            $permit->pickup_mode ?? '-',
            $permit->scan_token ? ($permit->scanned_at ? 'Ya' : 'Belum') : '—',
            $permit->scanned_at?->format('Y-m-d H:i') ?? '-',
            $permit->status ?? '-',
            $permit->emergency_contact_text ?? '-',
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FF0A5F9E']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }
}
