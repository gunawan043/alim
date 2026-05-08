<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentAchievementTemplateExport
{
    private string $typeLabel;
    private string $achievementType;
    private ?string $schoolId;

    public function __construct(string $typeLabel, string $achievementType, ?string $schoolId = null)
    {
        $this->typeLabel = $typeLabel;
        $this->achievementType = $achievementType;
        $this->schoolId = $schoolId;
    }

    public function download(string $filename = 'template_import_prestasi.xlsx')
    {
        $spreadsheet = new Spreadsheet();

        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('IMPORT PRESTASI');
        $this->buildTemplateSheet($ws);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('PETUNJUK');
        $this->buildGuideSheet($guideSheet);

        $ws->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempPath = storage_path("app/templates/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildTemplateSheet(Worksheet $ws): void
    {
        $DARK  = '1E3A5F';
        $MID   = '0590D6';
        $LIGHT = 'DBEAFE';
        $LGRAY = 'F1F5F9';
        $MGRAY = 'E2E8F0';
        $DGRAY = '475569';

        $cols = ['A','B','C','D','E','F','G','H'];

        $labels = [
            'NISN *',
            'Nama Siswa',
            'Nama Lomba / Kompetisi *',
            'Penyelenggara',
            'Tingkat',
            'Peringkat / Juara',
            'Tanggal (DD/MM/YYYY)',
            'Lokasi / Keterangan',
        ];

        $typeColor = match ($this->achievementType) {
            'akademik'       => '0590D6',
            'hafalan_quran'  => '065F46',
            'hafalan_hadits' => '7C3AED',
            default          => '0590D6',
        };

        // Title
        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', "TEMPLATE IMPORT — {$this->typeLabel}");
        $ws->getStyle('A1')->applyFromArray([
            'font'  => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $typeColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(32);

        $ws->mergeCells('A2:H2');
        $ws->setCellValue('A2', 'Nama file gambar piagam: {NISN}.{ekstensi} — contoh: 0012345678.jpg. Ekstensi: .jpg, .jpeg, .png, .pdf');
        $ws->getStyle('A2')->applyFromArray([
            'font'  => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF' . $DGRAY]],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $LIGHT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $ws->getRowDimension(2)->setRowHeight(16);

        // Header row
        $ws->getRowDimension(3)->setRowHeight(32);
        foreach ($cols as $i => $col) {
            $ws->setCellValue($col . '3', $labels[$i]);
            $ws->getStyle($col . '3')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $MID]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
            ]);
        }

        // Sample rows
        $samples = [
            ['0012345678', 'Ahmad Fauzi', 'Olimpiade Matematika Tingkat Provinsi', 'Dinas Pendidikan Prov. Jawa Barat', 'provinsi', 'juara 1', '15/03/2024', ''],
            ['0012345679', 'Siti Nurhaliza', 'Lomba Bahasa Inggris Nasional', 'Kemendikbudristek', 'nasional', 'juara 2', '20/05/2024', 'Jakarta'],
            ['0012345680', 'Budi Santoso', 'Lomba Cerdas Cermat', 'MTs Negeri 1', 'internal', 'peserta', '10/01/2024', 'Aula Madrasah'],
        ];

        foreach ($samples as $si => $row) {
            $r = $si + 4;
            $bg = ($si % 2 === 0) ? $LGRAY : 'FFFFFF';
            $ws->getRowDimension($r)->setRowHeight(22);
            foreach ($cols as $ci => $col) {
                $ws->setCellValue($col . $r, $row[$ci] ?? '');
                $ws->getStyle($col . $r)->applyFromArray([
                    'font'   => ['size' => 9],
                    'fill'   => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $bg]],
                    'alignment' => [
                        'horizontal' => $ci === 0 ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                ]);
            }
        }

        // Legend row
        $legendRow = 8;
        $ws->mergeCells("A{$legendRow}:H{$legendRow}");
        $ws->setCellValue("A{$legendRow}", 'LEGENDA');
        $ws->getStyle("A{$legendRow}")->applyFromArray([
            'font'  => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $DGRAY]],
        ]);
        $ws->getRowDimension($legendRow)->setRowHeight(16);

        $ws->mergeCells("A9:H9");
        $ws->setCellValue('A9', 'Tingkat: Internal | Kecamatan | Kabupaten/Kota | Provinsi | Nasional | Internasional     Peringkat: Juara 1 | Juara 2 | Juara 3 | Harapan 1 | Harapan 2 | Harapan 3 | Peserta | Lainnya');
        $ws->getStyle('A9')->applyFromArray([
            'font'  => ['italic' => true, 'size' => 8, 'color' => ['argb' => 'FF' . $DGRAY]],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $MGRAY]],
        ]);
        $ws->getRowDimension(9)->setRowHeight(14);

        // Column widths
        $widths = [16, 24, 36, 28, 18, 18, 22, 28];
        foreach ($cols as $i => $col) {
            $ws->getColumnDimension($col)->setWidth($widths[$i]);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $MID   = '0590D6';
        $DGRAY = '475569';
        $LGRAY = 'F1F5F9';

        // Title
        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'PETUNJUK IMPORT DATA PRESTASI');
        $ws->getStyle('A1')->applyFromArray([
            'font'  => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $MID]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(28);

        $rows = [
            ['', 'Berikut adalah panduan untuk mengisi template import:', ''],
            ['', '', ''],
            ['1', 'Kolom NISN', 'Wajib diisi. Gunakan NISN yang sudah terdaftar di sistem.'],
            ['2', 'Nama Siswa', 'Opsional — digunakan sebagai fallback jika NISN tidak cocok.'],
            ['3', 'Nama Lomba', 'Wajib diisi. Nama lengkap lomba/kompetisi/kejuaraan.'],
            ['4', 'Penyelenggara', 'Nama instansi/organisasi yang mengadakan lomba.'],
            ['5', 'Tingkat', 'Pilih: Internal | Kecamatan | Kabupaten/Kota | Provinsi | Nasional | Internasional'],
            ['6', 'Peringkat', 'Pilih: Juara 1 | Juara 2 | Juara 3 | Harapan 1 | Harapan 2 | Harapan 3 | Peserta | Lainnya'],
            ['7', 'Tanggal', 'Format: DD/MM/YYYY — contoh: 15/03/2024'],
            ['8', 'Lokasi / Keterangan', 'Tempat acara atau keterangan tambahan.'],
            ['', '', ''],
            ['⚠', 'File Gambar Piagam', 'Upload file gambar terpisah dari form import. Sistem akan mencocokkan berdasarkan NISN.'],
            ['📁', 'Format File', 'Nama file = NISN + ekstensi. Contoh: 0012345678.jpg'],
            ['✓', 'Ekstensi', 'jpg, jpeg, png, pdf'],
            ['', '', ''],
            ['📌', 'Catatan Penting', 'Baris dengan NISN kosong atau tidak valid akan dilewati.'],
        ];

        $ws->getRowDimension(2)->setRowHeight(16);
        $ws->setCellValue('A2', 'Petunjuk');
        $ws->setCellValue('B2', 'Kolom');
        $ws->setCellValue('C2', 'Deskripsi');
        foreach (['A','B','C'] as $col) {
            $ws->getStyle($col . '2')->applyFromArray([
                'font'  => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $MID]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        foreach ($rows as $i => $row) {
            $r = $i + 3;
            $ws->getRowDimension($r)->setRowHeight(18);
            $isHeader = $row[0] === '';
            foreach ($row as $ci => $val) {
                $col = ['A','B','C'][$ci];
                $ws->setCellValue($col . $r, $val);
                if ($i === 0) {
                    $ws->mergeCells("A{$r}:E{$r}");
                    $ws->getStyle("A{$r}")->applyFromArray([
                        'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF' . $DGRAY]],
                    ]);
                    $ws->getRowDimension($r)->setRowHeight(14);
                } elseif ($row[0] !== '' && $ci === 0 && is_numeric($row[0])) {
                    $ws->getStyle("A{$r}")->applyFromArray([
                        'font'  => ['bold' => true, 'size' => 9],
                        'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $LGRAY]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                    ]);
                }
            }
        }

        $ws->getColumnDimension('A')->setWidth(20);
        $ws->getColumnDimension('B')->setWidth(24);
        $ws->getColumnDimension('C')->setWidth(50);
    }
}