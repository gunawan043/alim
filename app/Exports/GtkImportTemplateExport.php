<?php

namespace App\Exports;

use App\Models\JenisGtk;
use App\Models\StructuralPosition;
use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GtkImportTemplateExport implements Export, WithMultipleSheets
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

class DataSheet implements Export, FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Kolom template: nama kolom => lebar. Urutan array = urutan kolom di Excel.
     * Kolom "satuan_kerja" dibaca per baris supaya satu file bisa memuat GTK
     * dari beberapa satuan kerja sekaligus.
     */
    public const COLUMNS = [
        // ── 1. Informasi pribadi ──
        'name' => 22,
        'email' => 26,
        'nik' => 20,
        'no_kk' => 20,
        'tempat_lahir' => 16,
        'tanggal_lahir' => 14,
        'jenis_kelamin' => 8,
        'golongan_darah' => 8,
        'agama' => 12,
        'status_perkawinan' => 14,
        'npwp' => 20,
        'no_hp' => 18,
        'no_whatsapp' => 18,
        'alamat_jalan' => 28,
        'alamat_rt_rw' => 12,
        'alamat_desa' => 22,
        'alamat_kecamatan' => 20,
        'alamat_kota' => 18,
        'alamat_provinsi' => 18,
        'kode_pos' => 12,

        // ── 2. Kepegawaian ──
        // Pangkat/golongan sengaja tidak disertakan: lembaga swasta tidak memakainya.
        'nupy' => 16,
        'jenis_gtk' => 20,
        'jabatan' => 22,
        'satuan_kerja' => 26,
        'status_kepegawaian' => 16,
        'tmt' => 14,
        'nomor_sk' => 18,
        'tanggal_sk' => 14,

        // ── 3. Pendidikan ──
        'jenjang_pendidikan' => 14,
        'nama_sekolah' => 28,
        'jurusan' => 20,
        'tahun_lulus' => 12,

        // ── 4. Keluarga ──
        // NUPY pasangan tidak perlu diisi: sistem menautkan otomatis bila
        // nama pasangan cocok dengan GTK yang sudah terdaftar.
        'nama_pasangan' => 22,
        'nama_anak_1' => 20,
        'tanggal_lahir_anak_1' => 16,
        'jenis_kelamin_anak_1' => 10,
        'nama_anak_2' => 20,
        'tanggal_lahir_anak_2' => 16,
        'jenis_kelamin_anak_2' => 10,
        'nama_anak_3' => 20,
        'tanggal_lahir_anak_3' => 16,
        'jenis_kelamin_anak_3' => 10,
    ];

    public function collection(): Collection
    {
        $example = [
            // 1. Informasi pribadi
            'name' => 'Fulan bin Abu',
            'email' => 'fulan.bin@example.com',
            'nik' => '3201234567890123',
            'no_kk' => '3201234567890001',
            'tempat_lahir' => 'Mataram',
            'tanggal_lahir' => '1990-01-15',
            'jenis_kelamin' => 'L',
            'golongan_darah' => 'A',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'npwp' => '123456789012345',
            'no_hp' => '081234567890',
            'no_whatsapp' => '081234567890',
            'alamat_jalan' => 'Jl. Contoh No. 123',
            'alamat_rt_rw' => '001/002',
            'alamat_desa' => 'Kelurahan Contoh',
            'alamat_kecamatan' => 'Kecamatan Contoh',
            'alamat_kota' => 'Kota Contoh',
            'alamat_provinsi' => 'Provinsi Contoh',
            'kode_pos' => '12345',

            // 2. Kepegawaian
            'nupy' => 'GTK2024001',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
            'satuan_kerja' => $this->exampleWorkUnitName(),
            'status_kepegawaian' => 'Tetap',
            'tmt' => '2020-01-01',
            'nomor_sk' => 'SK/001/2020',
            'tanggal_sk' => '2020-01-01',

            // 3. Pendidikan
            'jenjang_pendidikan' => 'S1',
            'nama_sekolah' => 'Universitas Islam Negeri',
            'jurusan' => 'Pendidikan Agama Islam',
            'tahun_lulus' => '2015',

            // 4. Keluarga
            'nama_pasangan' => 'Fulanah binti Abu',
            'nama_anak_1' => 'Anak Pertama',
            'tanggal_lahir_anak_1' => '2015-01-15',
            'jenis_kelamin_anak_1' => 'P',
            'nama_anak_2' => 'Anak Kedua',
            'tanggal_lahir_anak_2' => '2018-06-10',
            'jenis_kelamin_anak_2' => 'L',
        ];

        return collect([
            array_keys(self::COLUMNS),
            array_map(fn ($column) => $example[$column] ?? '', array_keys(self::COLUMNS)),
        ]);
    }

    /**
     * Contoh satuan kerja memakai unit nyata dari data seeder supaya pengguna
     * melihat format nama yang benar, bukan nama lembaga karangan.
     */
    private function exampleWorkUnitName(): string
    {
        $name = WorkUnit::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->first()
            ?->name;

        return $name ?: 'Nama Satuan Kerja';
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
        $widths = [];
        $index = 1;

        foreach (self::COLUMNS as $width) {
            $widths[Coordinate::stringFromColumnIndex($index++)] = $width;
        }

        return $widths;
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
            'A1:'.Coordinate::stringFromColumnIndex(count(self::COLUMNS)).'2' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }
}

class JenisGtkSheet implements Export, FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
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

class JabatanSheet implements Export, FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
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

class StatusKepegawaianSheet implements Export, FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
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
            ['Wafat', 'Wafat'],
            ['Pensiun', 'Pensiun'],
            ['Berhenti', 'Berhenti'],
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

class SatuanKerjaSheet implements Export, FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
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
