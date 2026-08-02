<?php

namespace App\Exports;

use App\Models\AssetCategory;
use App\Models\AssetRoom;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetTemplateExport
{
    private ?string $forcedRoomName;

    public function __construct(?string $forcedRoomName = null)
    {
        $this->forcedRoomName = $forcedRoomName;
    }

    public function download(string $filename = 'template_import_aset.xlsx')
    {
        $spreadsheet = new Spreadsheet;

        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('IMPORT ASET');
        $this->buildTemplateSheet($ws);

        $catSheet = $spreadsheet->createSheet();
        $catSheet->setTitle('KATEGORI');
        $this->buildCategorySheet($catSheet);

        $roomSheet = $spreadsheet->createSheet();
        $roomSheet->setTitle('RUANG');
        $this->buildRoomSheet($roomSheet);

        $ws->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        // Save to temp file and return as download
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempPath = storage_path("app/templates/{$filename}");
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildTemplateSheet(Worksheet $ws): void
    {
        $DARK = '1E3A5F';
        $MID = '2563EB';
        $LIGHT = 'DBEAFE';
        $LGRAY = 'F8FAFC';
        $MGRAY = 'E2E8F0';
        $DGRAY = '475569';

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];

        $labels = [
            'No', 'Nama Aset *', 'Kode Aset', 'Ruang *', 'Kategori *',
            'Merk', 'Model / Tipe', 'No Seri', 'Warna',
            'Kondisi', 'Status', 'Tahun Perolehan', 'Harga Perolehan (Rp)',
            'Sumber Perolehan', 'Sumber Dana', 'Spesifikasi', 'Catatan',
        ];

        // Title
        $ws->mergeCells('A1:Q1');
        $ws->setCellValue('A1', 'TEMPLATE IMPORT ASET — SARANA PRASARANA');
        $ws->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$DARK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(32);

        $ws->mergeCells('A2:Q2');
        $ws->setCellValue('A2', 'Isi kolom sesuai kebutuhan. Kolom bertanda * wajib diisi. Baris contoh di bawah bisa dihapus atau diedit.');
        $ws->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF'.$DGRAY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$LIGHT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $ws->getRowDimension(2)->setRowHeight(16);

        // Header row
        $ws->getRowDimension(3)->setRowHeight(32);
        foreach ($cols as $i => $col) {
            $ws->setCellValue($col.'3', $labels[$i]);
            $ws->getStyle($col.'3')->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$MID]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
            ]);
        }

        // Sample data rows
        $roomCol = $this->forcedRoomName ?? 'Kelas 10 X-A';
        $year = date('Y');

        $samples = [
            [1, 'Meja Siswa Single', 'AST-001', $roomCol, 'Meubelair (Alat Rumah Tangga)', 'Cosco', 'DX-200', '', 'Coklat', 'Baik', 'Tersedia', $year, '1500000', 'Pembelian', 'APBD', 'Ukuran 60x40cm, kaki pipa', ''],
            [2, 'Kursi Siswa', 'AST-002', $roomCol, 'Meubelair (Alat Rumah Tangga)', 'Cosco', 'KC-100', '', 'Coklat', 'Baik', 'Tersedia', $year, '500000', 'Pembelian', 'APBD', '', ''],
            [3, 'Meja Guru', 'AST-003', $roomCol, 'Meubelair (Alat Rumah Tangga)', 'Lionco', 'MG-01', '', 'Coklat', 'Baik', 'Tersedia', $year, '2000000', 'Pembelian', 'APBD', 'Ukuran 120x60cm', ''],
            [4, 'Whiteboard', 'AST-004', $roomCol, 'Peralatan Kantor', 'Modera', 'WB-120', '', 'Putih', 'Baik', 'Tersedia', $year, '800000', 'Pembelian', 'BOS', '120x90cm, frame aluminium', ''],
            [5, 'AC Split 1 PK', 'AST-005', $roomCol, 'Elektronik (Alat Elektronik)', 'Daikin', 'FTKC25', '', 'Putih', 'Baik', 'Tersedia', $year, '4500000', 'Pembelian', 'APBD', '1 PK, inverter', ''],
        ];

        foreach ($samples as $si => $row) {
            $r = $si + 4;
            $bg = ($si % 2 === 0) ? $LGRAY : 'FFFFFF';
            $ws->getRowDimension($r)->setRowHeight(22);
            foreach ($cols as $ci => $col) {
                $ws->setCellValue($col.$r, $row[$ci] ?? '');
                $ws->getStyle($col.$r)->applyFromArray([
                    'font' => ['size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$bg]],
                    'alignment' => [
                        'horizontal' => $ci === 0 ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                ]);
            }
        }

        // Legend
        $legendRow = 56;
        $ws->mergeCells("A{$legendRow}:Q{$legendRow}");
        $ws->setCellValue("A{$legendRow}", 'LEGENDA');
        $ws->getStyle("A{$legendRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$DGRAY]],
        ]);
        $ws->getRowDimension($legendRow)->setRowHeight(16);

        $ws->mergeCells('A57:Q57');
        $ws->setCellValue('A57', 'Kondisi: Baik | Rusak Ringan | Rusak Sedang | Rusak Berat    |    Status: Tersedia | Dipinjam | Dalam Perbaikan    |    Sumber: Pembelian | Hibah | BOS | Pemerintah');
        $ws->getStyle('A57')->applyFromArray([
            'font' => ['italic' => true, 'size' => 8, 'color' => ['argb' => 'FF'.$DGRAY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$MGRAY]],
        ]);
        $ws->getRowDimension(57)->setRowHeight(14);

        // Column widths
        $widths = [5, 24, 14, 22, 28, 14, 14, 14, 10, 16, 16, 14, 18, 18, 14, 28, 18];
        foreach ($cols as $i => $col) {
            $ws->getColumnDimension($col)->setWidth($widths[$i]);
        }
    }

    private function buildCategorySheet(Worksheet $ws): void
    {
        $MID = '2563EB';
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        $ws->mergeCells('A1:C1');
        $ws->setCellValue('A1', 'DAFTAR KATEGORI ASET');
        $ws->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$MID]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(28);

        foreach (['A', 'B', 'C'] as $col) {
            $ws->getStyle($col.'2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$MID]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        $ws->setCellValue('A2', 'Kode');
        $ws->setCellValue('B2', 'Nama Kategori');
        $ws->setCellValue('C2', 'Tipe Aset');
        $ws->getRowDimension(2)->setRowHeight(20);

        $typeMap = ['bergerak' => 'Bergerak', 'tidak_bergerak' => 'Tidak Bergerak', 'habis_pakai' => 'Habis Pakai'];
        foreach ($categories as $i => $cat) {
            $r = $i + 3;
            $bg = ($i % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $ws->setCellValue("A{$r}", $cat->code);
            $ws->setCellValue("B{$r}", $cat->name);
            $ws->setCellValue("C{$r}", $typeMap[$cat->asset_type] ?? $cat->asset_type);
            foreach (['A', 'B', 'C'] as $col) {
                $ws->getStyle("{$col}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$bg]],
                    'font' => ['size' => 9],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                ]);
            }
            $ws->getRowDimension($r)->setRowHeight(18);
        }

        $ws->getColumnDimension('A')->setWidth(16);
        $ws->getColumnDimension('B')->setWidth(42);
        $ws->getColumnDimension('C')->setWidth(20);
    }

    private function buildRoomSheet(Worksheet $ws): void
    {
        $MID = '2563EB';
        $rooms = AssetRoom::where('is_active', true)->orderBy('room_name')->get();

        $ws->mergeCells('A1:D1');
        $ws->setCellValue('A1', 'DAFTAR RUANG');
        $ws->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$MID]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(28);

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $ws->getStyle($col.'2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$MID]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        $ws->setCellValue('A2', 'Nama Ruang');
        $ws->setCellValue('B2', 'Kode');
        $ws->setCellValue('C2', 'Tipe');
        $ws->setCellValue('D2', 'Kapasitas');
        $ws->getRowDimension(2)->setRowHeight(20);

        foreach ($rooms as $i => $room) {
            $r = $i + 3;
            $bg = ($i % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $ws->setCellValue("A{$r}", $room->room_name);
            $ws->setCellValue("B{$r}", $room->room_code ?? '-');
            $ws->setCellValue("C{$r}", ucfirst(str_replace('_', ' ', $room->room_type)));
            $ws->setCellValue("D{$r}", $room->capacity ?? '-');
            foreach (['A', 'B', 'C', 'D'] as $col) {
                $ws->getStyle("{$col}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$bg]],
                    'font' => ['size' => 9],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                ]);
            }
            $ws->getRowDimension($r)->setRowHeight(18);
        }

        $ws->getColumnDimension('A')->setWidth(28);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(18);
        $ws->getColumnDimension('D')->setWidth(12);
    }
}
