<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use App\Models\Dormitory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DormitoryRoomsTemplateExport implements Export
{
    public function __construct(private readonly Dormitory $dormitory) {}

    public function download(string $filename = 'template_import_kamar_asrama.xlsx')
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KAMAR');

        $headers = [
            'kode_gedung',
            'nama_gedung',
            'kode_kamar',
            'nama_kamar',
            'kapasitas',
            'lantai',
            'tipe_kamar',
            'gender',
            'aktif',
            'catatan',
        ];

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT KAMAR & GEDUNG ASRAMA');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Asrama: '.$this->dormitory->name.' | Kolom kode_kamar, nama_kamar, dan kapasitas wajib diisi. Gender kosong mengikuti gender asrama.');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column.'3', $header);
            $sheet->getStyle($column.'3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
        }

        $samples = [
            ['GED-A', 'Gedung A', 'KMR-A01', 'Kamar A-01', 6, 1, 'Reguler', 'putra', 'ya', 'Contoh baris — silakan ganti'],
            ['GED-A', 'Gedung A', 'KMR-A02', 'Kamar A-02', 4, 1, 'Reguler', 'putra', 'ya', ''],
            ['GED-B', 'Gedung B', 'KMR-B01', 'Kamar B-01', 8, 1, 'Tahfidz', 'putra', 'ya', ''],
        ];

        foreach ($samples as $rowIndex => $sample) {
            $excelRow = $rowIndex + 4;
            foreach ($sample as $columnIndex => $value) {
                $sheet->setCellValue(chr(65 + $columnIndex).$excelRow, $value);
                $sheet->getStyle(chr(65 + $columnIndex).$excelRow)->applyFromArray([
                    'font' => ['size' => 9],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
            }
        }

        $widths = [14, 24, 16, 28, 12, 10, 18, 14, 10, 32];
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }

        $sheet->freezePane('A4');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $path = storage_path('app/templates/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
