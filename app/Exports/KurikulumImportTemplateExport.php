<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\SchoolGroupService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KurikulumImportTemplateExport
{
    private const NAVY = '1E3A5F';

    private const BLUE = '0590D6';

    private const LIGHT = 'DBEAFE';

    private const GRAY = 'F1F5F9';

    private const BORDER = 'CBD5E1';

    public function __construct(
        private readonly School $school,
        private readonly ?AcademicYear $academicYear,
        private readonly string $semester = 'ganjil'
    ) {}

    public function download(string $filename = 'template_import_kurikulum.xlsx')
    {
        $spreadsheet = new Spreadsheet;

        $this->buildDataSheet($spreadsheet->getActiveSheet(), 'CP', [
            'Tahun Ajaran', 'Kode Mapel', 'Mapel', 'Fase', 'Elemen', 'Deskripsi', 'Urutan', 'Aktif',
        ], [16, 14, 28, 10, 24, 70, 10, 10], 'Satu baris = satu Capaian Pembelajaran. Kunci: Mapel + Fase + Elemen + Tahun Ajaran.');

        $this->buildDataSheet($spreadsheet->createSheet()->setTitle('TP'), 'TP', [
            'Tahun Ajaran', 'Semester', 'Kode Mapel', 'Mapel', 'Kelas', 'Kode TP', 'Deskripsi', 'Elemen', 'Alokasi Waktu', 'Urutan', 'Aktif', 'CP', 'KBC Values', 'KBC Indicator',
        ], [16, 12, 14, 28, 14, 14, 70, 22, 12, 10, 10, 30, 22, 40], 'Satu baris = satu Tujuan Pembelajaran. Kunci: Mapel + Kelas + Kode TP + TA + Semester. Kolom CP opsional: mapel|fase|elemen.');

        $this->buildDataSheet($spreadsheet->createSheet()->setTitle('ATP'), 'ATP', [
            'Tahun Ajaran', 'Semester', 'Kode Mapel', 'Mapel', 'Kelas', 'Guru', 'Status', 'Catatan',
        ], [16, 12, 14, 28, 14, 28, 12, 40], 'Satu baris = satu ATP. Kunci: Mapel + Kelas + TA + Semester. Status: draft/published.');

        $this->buildDataSheet($spreadsheet->createSheet()->setTitle('ATP_ITEM'), 'ATP_ITEM', [
            'Tahun Ajaran', 'Semester', 'Kode Mapel', 'Mapel', 'Kelas', 'Kode TP', 'Urutan', 'JP', 'Catatan',
        ], [16, 12, 14, 28, 14, 14, 10, 10, 40], 'Item ATP. Jika satu ATP memiliki baris di sheet ini, seluruh item ATP tersebut diganti dengan daftar di sini.');

        $this->buildDataSheet($spreadsheet->createSheet()->setTitle('PERANGKAT'), 'PERANGKAT', [
            'Tahun Ajaran', 'Semester', 'Kode Mapel', 'Mapel', 'Kelas', 'Rombel', 'Guru', 'Judul', 'Tipe', 'Status', 'Catatan', 'Desain JSON',
        ], [16, 12, 14, 28, 14, 14, 28, 40, 12, 12, 40, 60], 'Perangkat/RPM. Kunci: Mapel + Kelas + TA + Semester (satu dokumen current). Desain JSON opsional; hanya kunci desain yang dikenal yang dipakai.');

        $this->buildDataSheet($spreadsheet->createSheet()->setTitle('PROTA'), 'PROTA', [
            'Tahun Ajaran', 'Semester', 'Kode Mapel', 'Mapel', 'Kelas', 'Guru', 'Status', 'Catatan',
        ], [16, 12, 14, 28, 14, 28, 12, 40], 'PROTA dibuat dari ATP. Jika sudah ada dan opsi perbarui aktif, item PROTA disinkronkan ulang dari ATP.');

        $this->buildDataSheet($spreadsheet->createSheet()->setTitle('PROSEM'), 'PROSEM', [
            'Tahun Ajaran', 'Semester', 'Kode Mapel', 'Mapel', 'Kelas', 'Guru', 'Status', 'Catatan',
        ], [16, 12, 14, 28, 14, 28, 12, 40], 'PROSEM dibuat dari PROTA dan Pekan Efektif. Jika sudah ada, distribusi disinkronkan ulang.');

        $this->buildGuideSheet($spreadsheet->createSheet()->setTitle('PETUNJUK'));
        $this->buildSubjectReference($spreadsheet->createSheet()->setTitle('REF_MAPEL'));
        $this->buildGradeReference($spreadsheet->createSheet()->setTitle('REF_KELAS'));
        $this->buildTeacherReference($spreadsheet->createSheet()->setTitle('REF_GURU'));
        $this->buildCpReference($spreadsheet->createSheet()->setTitle('REF_CP'));
        $this->buildTpReference($spreadsheet->createSheet()->setTitle('REF_TP'));
        $this->buildAtpReference($spreadsheet->createSheet()->setTitle('REF_ATP'));

        $spreadsheet->setActiveSheetIndex(0);

        $path = storage_path("app/templates/{$filename}");
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildDataSheet(Worksheet $sheet, string $title, array $headers, array $widths, string $note): void
    {
        $sheet->setTitle($title);
        $this->writeHeader($sheet, $headers, $widths);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$this->columnLetter(count($headers)).'1');

        $sheet->mergeCells('A2:'.$this->columnLetter(count($headers)).'2');
        $sheet->setCellValue('A2', $note);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::LIGHT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(30);
    }

    private function buildGuideSheet(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'PETUNJUK IMPORT KURIKULUM');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $lines = [
            ['1.', 'Isi sheet CP → TP → ATP → ATP_ITEM → PERANGKAT → PROTA → PROSEM sesuai urutan.'],
            ['2.', 'Nama mapel/kelas/guru harus ada di master. Gunakan sheet REF_* sebagai acuan.'],
            ['3.', 'CP: kunci Mapel + Fase + Elemen + Tahun Ajaran.'],
            ['4.', 'TP: kunci Mapel + Kelas + Kode TP + Tahun Ajaran + Semester. Kolom CP opsional.'],
            ['5.', 'ATP: kunci Mapel + Kelas + Tahun Ajaran + Semester.'],
            ['6.', 'ATP_ITEM: hanya untuk ATP yang barisnya ada di sheet ini; item lain tidak diubah.'],
            ['7.', 'PROTA/PROSEM dibuat dari ATP/PROTA. Jika sudah ada dan opsi perbarui aktif, item/distribusi disinkronkan ulang.'],
            ['8.', 'Perangkat: satu dokumen current per Mapel + Kelas + TA + Semester. Desain JSON opsional.'],
            ['9.', 'Data yang sudah ada diperbarui bila opsi "Perbarui data lama" dicentang; jika tidak, dilaporkan sebagai error.'],
            ['10.', 'Baris yang gagal dilaporkan per baris; baris lain tetap diproses.'],
        ];

        $row = 3;
        foreach ($lines as [$number, $text]) {
            $sheet->setCellValue('A'.$row, $number);
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue('B'.$row, $text);
            $sheet->getStyle("A{$row}:F{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(30);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A'.$row, 'CONTOH');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $examples = [
            ['CP', 'Matematika | Fase D | Bilangan | Memahami operasi bilangan bulat...'],
            ['TP', 'Matematika | Kelas 7 | TP-01 | Menjelaskan operasi bilangan bulat | 4 JP'],
            ['ATP', 'Matematika | Kelas 7 | 2026/2027 | ganjil'],
            ['ATP_ITEM', 'Matematika | Kelas 7 | TP-01 | urutan 1 | 4 JP'],
            ['PERANGKAT', 'Matematika | Kelas 7 | judul RPM | desain JSON opsional'],
            ['PROTA/PROSEM', 'Isi setelah ATP/PROTA tersedia.'],
        ];
        foreach ($examples as [$label, $text]) {
            $row++;
            $sheet->setCellValue('A'.$row, $label);
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue('B'.$row, $text);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        }

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(110);
    }

    private function buildSubjectReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Kode Mapel', 'Nama Mapel'], [18, 40]);
        $subjects = \App\Models\Subject::where('school_id', $this->school->id)
            ->where('is_active', true)->orderBy('name')->get(['code', 'name']);
        $row = 2;
        foreach ($subjects as $subject) {
            $this->writeRow($sheet, $row++, [$subject->code, $subject->name]);
        }
        $sheet->freezePane('A2');
    }

    private function buildGradeReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Kelas', 'Tingkat', 'Fase'], [22, 12, 10]);
        $grades = GradeLevel::where('school_id', $this->school->id)->orderBy('level')->get();
        $row = 2;
        foreach ($grades as $grade) {
            $this->writeRow($sheet, $row++, [$grade->name, $grade->level, $grade->fase]);
        }
        $sheet->freezePane('A2');
    }

    private function buildTeacherReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Nama Guru', 'Jabatan'], [40, 34]);
        $ids = SchoolGroupService::userIdsForSchool($this->school->id);
        $users = User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
        $row = 2;
        foreach ($users as $user) {
            $position = \App\Models\GtkWorkUnit::where('user_id', $user->id)
                ->where('work_unit_id', $this->school->work_unit_id)
                ->value('jabatan');
            $this->writeRow($sheet, $row++, [$user->name, $position ?: 'GTK']);
        }
        $sheet->freezePane('A2');
    }

    private function buildCpReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Mapel', 'Fase', 'Elemen', 'Deskripsi'], [28, 10, 24, 70]);
        $cps = CapaianPembelajaran::with('subject:id,name')
            ->where('school_id', $this->school->id)
            ->when($this->academicYear, fn ($q) => $q->where('academic_year_id', $this->academicYear->id))
            ->orderBy('fase')->orderBy('urutan')->get();
        $row = 2;
        foreach ($cps as $cp) {
            $this->writeRow($sheet, $row++, [
                $cp->subject?->name,
                $cp->fase,
                $cp->elemen,
                mb_substr((string) $cp->deskripsi, 0, 160),
            ]);
        }
        $sheet->freezePane('A2');
    }

    private function buildTpReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Mapel', 'Kelas', 'Kode TP', 'Deskripsi'], [28, 16, 14, 70]);
        $tps = TujuanPembelajaran::with(['subject:id,name', 'gradeLevel:id,name'])
            ->where('school_id', $this->school->id)
            ->where('semester', $this->semester)
            ->when($this->academicYear, fn ($q) => $q->where('academic_year_id', $this->academicYear->id))
            ->orderBy('urutan')->orderBy('kode_tp')->get();
        $row = 2;
        foreach ($tps as $tp) {
            $this->writeRow($sheet, $row++, [
                $tp->subject?->name,
                $tp->gradeLevel?->name,
                $tp->kode_tp,
                mb_substr((string) $tp->deskripsi, 0, 160),
            ]);
        }
        $sheet->freezePane('A2');
    }

    private function buildAtpReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Mapel', 'Kelas', 'Tahun Ajaran', 'Semester'], [28, 16, 16, 12]);
        $atps = AlurTujuanPembelajaran::with(['subject:id,name', 'gradeLevel:id,name'])
            ->where('school_id', $this->school->id)
            ->where('semester', $this->semester)
            ->when($this->academicYear, fn ($q) => $q->where('academic_year_id', $this->academicYear->id))
            ->orderBy('subject_id')->get();
        $row = 2;
        foreach ($atps as $atp) {
            $this->writeRow($sheet, $row++, [
                $atp->subject?->name,
                $atp->gradeLevel?->name,
                $this->academicYear?->name,
                $atp->semester,
            ]);
        }
        $sheet->freezePane('A2');
    }

    private function writeHeader(Worksheet $sheet, array $headers, array $widths): void
    {
        foreach ($headers as $index => $header) {
            $column = $this->columnLetter($index + 1);
            $sheet->setCellValue($column.'1', $header);
            $sheet->getColumnDimension($column)->setWidth($widths[$index] ?? 18);
        }

        $last = $this->columnLetter(count($headers));
        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::BLUE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF'.self::BORDER]]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);
    }

    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            $sheet->setCellValue($this->columnLetter($index + 1).$row, $value);
        }

        $last = $this->columnLetter(count($values));
        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font' => ['size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.($row % 2 === 0 ? self::GRAY : 'FFFFFF')]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF'.self::BORDER]]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
