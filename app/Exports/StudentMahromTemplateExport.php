<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template import mahrom format LEBAR:
 *  - satu baris = satu santri (informasi santri terisi otomatis);
 *  - data mahrom 1..N berada di kolom ke samping (bukan baris berulang),
 *    sehingga tidak ada duplikasi data santri.
 */
class StudentMahromTemplateExport implements Export
{
    private const MAHROM_FIELDS = [
        'nama' => 'Nama',
        'hubungan' => 'Hubungan',
        'no_hp' => 'No. HP',
        'nik' => 'NIK',
        'alamat' => 'Alamat',
        'utama' => 'Utama',
    ];

    public function __construct(
        private readonly bool $filled = false,
        private readonly ?Collection $students = null,
        private readonly int $maxMahrom = 4,
    ) {}

    public function download(string $filename = 'template_import_mahrom.xlsx')
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MAHROM');
        $this->buildDataSheet($sheet);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('PETUNJUK');
        $this->buildGuideSheet($guide);

        $spreadsheet->setActiveSheetIndex(0);

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A3)
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

    /**
     * @return array<int, string>
     */
    private function headers(): array
    {
        $headers = ['NISN', 'NIS', 'Nama Santri', 'Kelas', 'Kamar', 'Asrama'];

        for ($i = 1; $i <= $this->maxMahrom; $i++) {
            foreach (self::MAHROM_FIELDS as $label) {
                $headers[] = "Mahrom {$i} - {$label}";
            }
        }

        return $headers;
    }

    private function buildDataSheet(Worksheet $sheet): void
    {
        $headers = $this->headers();
        $lastColumn = $this->columnLetter(count($headers));

        // Warna bergantian per kelompok mahrom agar mudah dibaca.
        $bandColors = ['1E3A5F', '0F766E', '7C2D12', '4C1D95', '9D174D'];

        foreach ($headers as $index => $header) {
            $column = $this->columnLetter($index + 1);
            $isStudent = $index < 6;
            $groupIndex = $isStudent ? 0 : intdiv($index - 6, count(self::MAHROM_FIELDS));
            $color = $bandColors[$groupIndex % count($bandColors)];

            $sheet->setCellValue($column.'1', $header);
            $sheet->getStyle($column.'1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isStudent ? '334155' : $color]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
        }

        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->freezePane('A2');

        $widths = [14, 12, 28, 16, 16, 22];
        for ($i = 0; $i < $this->maxMahrom; $i++) {
            array_push($widths, 24, 12, 16, 20, 26, 9);
        }

        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension($this->columnLetter($index + 1))->setWidth($width);
        }

        // Dropdown validasi (hubungan & utama) untuk seluruh baris data.
        $lastRow = max(2, $this->filled ? ($this->students?->count() ?? 0) + 1 : 200);

        for ($i = 1; $i <= $this->maxMahrom; $i++) {
            $startIndex = 6 + (($i - 1) * count(self::MAHROM_FIELDS));
            $relationCol = $this->columnLetter($startIndex + 2); // Hubungan
            $primaryCol = $this->columnLetter($startIndex + 6);  // Utama

            for ($row = 2; $row <= $lastRow; $row++) {
                $sheet->getCell("{$relationCol}{$row}")->getDataValidation()
                    ->setType(DataValidation::TYPE_LIST)
                    ->setFormula1('"ayah,ibu,wali,kakak,adik,paman,bibi,kakek,nenek,suami,istri,sepupu,anak,lainnya"')
                    ->setAllowBlank(true);

                $sheet->getCell("{$primaryCol}{$row}")->getDataValidation()
                    ->setType(DataValidation::TYPE_LIST)
                    ->setFormula1('"ya,tidak"')
                    ->setAllowBlank(true);
            }
        }

        $sheet->setAutoFilter("A1:{$lastColumn}1");

        if (! $this->filled) {
            return;
        }

        $rowNumber = 2;

        foreach ($this->students ?? collect() as $student) {
            $studyGroup = $student->currentClassHistory?->studyGroup;
            $classLabel = trim(($studyGroup?->gradeLevel?->name ?? '').' '.($studyGroup?->name ?? ''));

            $resident = $student->activeDormitoryResident;
            $roomLabel = $resident?->room?->name ?: ($resident?->room?->code ?? '');

            $values = [
                (string) ($student->nisn ?? ''),
                (string) ($student->nis ?? ''),
                (string) ($student->name ?? ''),
                $classLabel,
                $roomLabel,
                (string) ($resident?->dormitory?->name ?? ''),
            ];

            $mahroms = $student->mahroms
                ->sortByDesc('is_primary')
                ->values()
                ->take($this->maxMahrom);

            for ($i = 0; $i < $this->maxMahrom; $i++) {
                $mahrom = $mahroms->get($i);

                array_push(
                    $values,
                    (string) ($mahrom?->name ?? ''),
                    (string) ($mahrom?->relationship_text ?? ''),
                    (string) ($mahrom?->phone ?? ''),
                    (string) ($mahrom?->id_number ?? ''),
                    (string) ($mahrom?->address ?? ''),
                    $mahrom ? ($mahrom->is_primary ? 'ya' : 'tidak') : '',
                );
            }

            foreach ($values as $index => $value) {
                $column = $this->columnLetter($index + 1);
                $sheet->setCellValue($column.$rowNumber, $value);
                $sheet->getStyle($column.$rowNumber)->applyFromArray([
                    'font' => ['size' => 9],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
            }

            $rowNumber++;
        }
    }

    private function buildGuideSheet(Worksheet $guide): void
    {
        $guide->setCellValue('A1', 'PETUNJUK IMPORT DATA MAHROM (FORMAT SATU BARIS PER SANTRI)');
        $guide->mergeCells('A1:B1');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $guide->getRowDimension(1)->setRowHeight(30);

        $rows = [
            ['Kolom', 'Keterangan'],
            ['NISN / NIS', 'Identitas santri. Wajib salah satu, dan sudah terisi otomatis pada template ini.'],
            ['Nama Santri / Kelas / Kamar / Asrama', 'Terisi otomatis dari data sistem — tidak perlu diubah (diabaikan saat import).'],
            ['Mahrom 1 - Nama s/d Mahrom N - Nama', 'Nama mahrom. Kosongkan jika tidak ada.'],
            ['Mahrom N - Hubungan', 'ayah / ibu / wali / kakak / adik / paman / bibi / kakek / nenek / suami / istri / sepupu / anak / lainnya.'],
            ['Mahrom N - No. HP', 'Nomor WhatsApp wali (8–20 digit). Boleh diawali +62.'],
            ['Mahrom N - NIK', 'Opsional, maks. 30 karakter.'],
            ['Mahrom N - Alamat', 'Opsional.'],
            ['Mahrom N - Utama', 'ya / tidak. Hanya satu mahrom utama per santri.'],
            ['', ''],
            ['Cara kerja', 'Satu baris = satu santri. Data mahrom diisi ke kolom samping, bukan menambah baris baru.'],
            ['', 'Mahrom dengan hubungan yang sama akan DIPERBARUI, bukan diduplikasi.'],
            ['', 'Kolom kosong TIDAK menghapus data lama (termasuk No. HP/alamat).'],
            ['', 'Contoh: Mahrom 1 - Nama "Bapak Ahmad" | Hubungan ayah | No. HP 081234567890 | Utama ya.'],
            ['', 'Tambahkan mahrom ke-2/ke-3 pada kolom Mahrom 2/3 di baris santri yang sama.'],
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

        $guide->getColumnDimension('A')->setWidth(40);
        $guide->getColumnDimension('B')->setWidth(95);
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
