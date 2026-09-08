<?php

namespace App\Exports;

use App\Models\JenisGtk;
use App\Models\StructuralPosition;
use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GtkImportTemplateExport implements WithMultipleSheets
{
    use Exportable;

    private string $workUnitName;

    public function __construct(string $workUnitName)
    {
        $this->workUnitName = $workUnitName;
    }

    public function sheets(): array
    {
        return [
            new DataSheet,
            new JenisGtkSheet,
            new JabatanSheet,
            new StatusKepegawaianSheet,
            new SatuanKerjaSheet($this->workUnitName),
        ];
    }
}

class DataSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return collect([
            // Header
            [
                'name', 'email', 'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir',
                'jenis_kelamin', 'golongan_darah', 'agama', 'status_perkawinan', 'npwp',
                'no_hp', 'no_whatsapp',
                'nupy', 'jenis_gtk', 'jabatan', 'status_kepegawaian', 'tmt', 'nomor_sk',
                'tanggal_sk', 'pangkat_golongan',
                'jenjang_pendidikan', 'nama_sekolah', 'jurusan', 'tahun_lulus',
                'alamat_jalan', 'alamat_rt_rw', 'alamat_desa', 'alamat_kecamatan',
                'alamat_kota', 'alamat_provinsi', 'kode_pos',
            ],
            // Contoh baris
            [
                'Fulan bin Abu', 'fulan.bin@example.com', '3201234567890123', '3201234567890001',
                'Mataram', '1990-01-15', 'L', 'A', 'Islam', 'Belum Kawin', '123456789012345',
                '081234567890', '081234567890',
                'GTK2024001', 'Pendidik / Guru', 'Guru Umum', 'Tetap', '2020-01-01',
                'SK/001/2020', '2020-01-01', 'III/a',
                'S1', 'Universitas Islam Negeri', 'Pendidikan Agama Islam', '2015',
                'Jl. Contoh No. 123', '001/002', 'Kelurahan Contoh', 'Kecamatan Contoh',
                'Kota Contoh', 'Provinsi Contoh', '12345',
            ],
        ]);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Data GTK';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 26, 'C' => 20, 'D' => 20, 'E' => 16, 'F' => 14,
            'G' => 5, 'H' => 5, 'I' => 10, 'J' => 12, 'K' => 18,
            'L' => 18, 'M' => 18, 'N' => 14, 'O' => 14, 'P' => 14, 'Q' => 14,
            'R' => 10, 'S' => 14, 'T' => 14, 'U' => 14, 'V' => 12,
            'W' => 28, 'X' => 12, 'Y' => 22, 'Z' => 20, 'AA' => 18, 'AB' => 18,
            'AC' => 16, 'AD' => 16, 'AE' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
            ],
            'A1:AE2' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}

class JenisGtkSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return JenisGtk::active()
            ->orderBy('urutan')
            ->get(['id', 'nama', 'deskripsi'])
            ->map(fn ($j) => [$j->id, $j->nama, $j->deskripsi ?? '']);
    }

    public function headings(): array
    {
        return ['ID UUID (opsional di template)', 'Nama Jenis GTK', 'Deskripsi'];
    }

    public function title(): string
    {
        return 'Referensi: Jenis GTK';
    }

    public function columnWidths(): array
    {
        return ['A' => 38, 'B' => 36, 'C' => 42];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF15803D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A1:C'.($this->collection()->count() + 1) => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}

class JabatanSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return StructuralPosition::active()
            ->orderBy('jenis_gtk_id')
            ->orderBy('urutan')
            ->orderBy('name')
            ->get(['name'])
            ->map(fn ($j) => [$j->name]);
    }

    public function headings(): array
    {
        return ['Nama Jabatan'];
    }

    public function title(): string
    {
        return 'Referensi: Jabatan';
    }

    public function columnWidths(): array
    {
        return ['A' => 32];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7C3AED']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A1:A'.($this->collection()->count() + 1) => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}

class StatusKepegawaianSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return collect([
            ['PTT', 'Pegawai Tidak Tetap'],
            ['PTY', 'Pegawai Tetap Yayasan'],
            ['GTT', 'Guru Tidak Tetap'],
            ['GTY', 'Guru Tetap Yayasan'],
            ['KONTRAK', 'Kontrak'],
            ['Percobaan', 'Percobaan'],
            ['Magang', 'Magang'],
        ]);
    }

    public function headings(): array
    {
        return ['Kode Status', 'Keterangan'];
    }

    public function title(): string
    {
        return 'Referensi: Status Kepegawaian';
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 28];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'DC2626']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A1:B'.($this->collection()->count() + 1) => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}

class SatuanKerjaSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    private string $selectedUnitName;

    public function __construct(string $selectedUnitName)
    {
        $this->selectedUnitName = $selectedUnitName;
    }

    public function collection(): Collection
    {
        return WorkUnit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($w) => [$w->id, $w->code ?? '-', $w->name]);
    }

    public function headings(): array
    {
        return ['ID UUID', 'Kode', 'Nama Satuan Kerja'];
    }

    public function title(): string
    {
        return 'Referensi: Satuan Kerja';
    }

    public function columnWidths(): array
    {
        return ['A' => 38, 'B' => 14, 'C' => 42];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'EA580C']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A1:C'.($this->collection()->count() + 1) => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}
