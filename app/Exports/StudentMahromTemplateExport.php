<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template import data mahrom/wali santri.
 * Baris 1 = header (dibaca oleh StudentMahromImport), sheet PETUNJUK berisi
 * panduan dan contoh pengisian.
 */
class StudentMahromTemplateExport implements Export
{
    private const HEADERS = [
        'NISN',
        'NIS',
        'Nama Mahrom',
        'Hubungan',
        'No. HP',
        'NIK',
        'Alamat',
        'Utama',
        'Aktif',
        'Catatan',
    ];

    public function download(string $filename = 'template_import_mahrom.xlsx')
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MAHROM');
        $this->buildDataSheet($sheet);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('PETUNJUK');
        $this->buildGuideSheet($guide);

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $dir = storage_path('app/templates');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/'.$filename;
        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildDataSheet(Worksheet $sheet): void
    {
        $lastColumn = chr(64 + count(self::HEADERS)); // J

        foreach (self::HEADERS as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column.'1', $header);
            $sheet->getStyle($column.'1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
        }

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->freezePane('A2');

        $widths = [14, 12, 28, 14, 18, 22, 32, 10, 10, 28];
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }

        // Contoh baris sebagai data valid (boleh dihapus pengguna).
        $sample = [
            '0000000000', '', 'Contoh Nama Ayah', 'ayah', '081234567890',
            '', 'Alamat lengkap (opsional)', 'ya', 'ya', 'Contoh baris — hapus sebelum import',
        ];
        foreach ($sample as $index => $value) {
            $sheet->setCellValue(chr(65 + $index).'2', $value);
            $sheet->getStyle(chr(65 + $index).'2')->applyFromArray([
                'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '64748B']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        }

        // Dropdown hubungan & pilihan ya/tidak.
        $relationships = '"ayah,ibu,wali,kakak,adik,paman,bibi,kakek,nenek,suami,istri,sepupu,anak,lainnya"';
        for ($row = 2; $row <= 500; $row++) {
            $sheet->getCell("D{$row}")->getDataValidation()
                ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                ->setFormula1($relationships)
                ->setAllowBlank(true);

            foreach (['H', 'I'] as $col) {
                $sheet->getCell("{$col}{$row}")->getDataValidation()
                    ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                    ->setFormula1('"ya,tidak"')
                    ->setAllowBlank(true);
            }
        }

        $sheet->setAutoFilter("A1:{$lastColumn}1");
    }

    private function buildGuideSheet(Worksheet $guide): void
    {
        $guide->setCellValue('A1', 'PETUNJUK IMPORT DATA MAHROM / WALI SANTRI');
        $guide->mergeCells('A1:B1');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $guide->getRowDimension(1)->setRowHeight(30);

        $rows = [
            ['Kolom', 'Keterangan'],
            ['NISN', 'Wajib salah satu: NISN atau NIS. Harus cocok dengan data santri yang dapat Anda akses.'],
            ['NIS', 'Alternatif pencocokan jika NISN kosong.'],
            ['Nama Mahrom', 'Wajib diisi.'],
            ['Hubungan', 'ayah / ibu / wali / kakak / adik / paman / bibi / kakek / nenek / suami / istri / sepupu / anak / lainnya.'],
            ['No. HP', 'Opsional. Nomor WhatsApp wali (8–20 digit). Boleh diawali +62.'],
            ['NIK', 'Opsional, maksimal 30 karakter. NIK yang sudah dipakai mahrom lain akan diabaikan.'],
            ['Alamat', 'Opsional.'],
            ['Utama', 'ya / tidak. Hanya satu mahrom utama per santri; mahrom pertama otomatis menjadi utama.'],
            ['Aktif', 'ya / tidak. Kosong dianggap ya.'],
            ['Catatan', 'Opsional.'],
            ['', ''],
            ['Perilaku import', 'Peran yang sudah ada (mis. "ayah") akan DIPERBARUI, bukan diduplikasi.'],
            ['', 'Kolom No. HP/alamat yang dikosongkan TIDAK menghapus data lama.'],
            ['', 'Baris dengan santri tidak ditemukan atau data tidak valid akan dilewati dan dilaporkan.'],
            ['', 'Maksimal mahrom per santri mengikuti pengaturan sistem (default 4).'],
            ['', 'Contoh: NISN 1234567890 | Nama Mahrom Bapak Ahmad | Hubungan ayah | No. HP 081234567890 | Utama ya'],
        ];

        $rowNumber = 3;
        foreach ($rows as $row) {
            $guide->setCellValue('A'.$rowNumber, $row[0]);
            $guide->setCellValue('B'.$rowNumber, $row[1]);
            $isHeader = $rowNumber === 3;
            $guide->getStyle('A'.$rowNumber.':B'.$rowNumber)->applyFromArray([
                'font' => ['bold' => $isHeader, 'size' => 9],
                'fill' => $isHeader
                    ? ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']]
                    : ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
            $rowNumber++;
        }

        $guide->getColumnDimension('A')->setWidth(22);
        $guide->getColumnDimension('B')->setWidth(95);
    }
}
