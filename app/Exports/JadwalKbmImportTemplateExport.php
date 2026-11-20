<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Services\JadwalGeneratorService;
use App\Services\TeacherAvailabilityService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JadwalKbmImportTemplateExport
{
    private const NAVY = '1E3A5F';

    private const BORDER = 'CBD5E1';

    public function __construct(
        private readonly School $school,
        private readonly AcademicYear $academicYear,
    ) {}

    public function download(string $filename = 'template_import_jadwal.xlsx')
    {
        $spreadsheet = new Spreadsheet;

        $this->buildJadwalSheet($spreadsheet->getActiveSheet());
        $this->buildGuideSheet($spreadsheet->createSheet()->setTitle('PETUNJUK'));
        $this->buildStudyGroupReference($spreadsheet->createSheet()->setTitle('REF_ROMBEL'));
        $this->buildSubjectReference($spreadsheet->createSheet()->setTitle('REF_MAPEL'));
        $this->buildTeacherReference($spreadsheet->createSheet()->setTitle('REF_GURU'));
        $this->buildSlotReference($spreadsheet->createSheet()->setTitle('REF_JAM'));

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template_import_jadwal_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path("app/templates/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildJadwalSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('JADWAL');

        $headers = ['Rombel', 'Hari', 'Jam Ke', 'Kode Mapel', 'Mapel', 'Guru', 'Ruang', 'Aktif', 'Catatan'];
        $widths = [14, 12, 10, 14, 28, 30, 14, 10, 32];

        $this->writeHeader($sheet, $headers, $widths);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:I1');
        $sheet->getStyle('A1:I1')->getAlignment()->setWrapText(true);
    }

    private function buildGuideSheet(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'PETUNJUK IMPORT JADWAL PELAJARAN');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $lines = [
            ['1.', 'Isi sheet JADWAL. Satu baris = satu pertemuan (rombel + hari + jam ke + mapel + guru).'],
            ['2.', 'Gunakan sheet REF_ROMBEL, REF_MAPEL, REF_GURU, dan REF_JAM sebagai acuan.'],
            ['3.', 'Rombel diisi nama atau kode rombel, contoh: 7A.'],
            ['4.', 'Hari boleh angka 1-6 atau nama hari: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu.'],
            ['5.', 'Jam Ke mengikuti nomor jam pada REF_JAM. Jam istirahat tidak boleh dipakai.'],
            ['6.', 'Kode Mapel lebih disarankan daripada nama mapel agar tidak ambigu.'],
            ['7.', 'Guru boleh dikosongkan untuk blok tim tahfidz. Jika diisi, gunakan nama/email/username dari REF_GURU.'],
            ['8.', 'Aktif: 1/ya/aktif atau 0/tidak/nonaktif. Kosong = mengikuti pilihan default saat import.'],
            ['9.', 'Jika jadwal pada rombel + hari + jam yang sama sudah ada, centang "Perbarui jadwal yang sudah ada" agar entri lama diperbarui.'],
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
        $sheet->setCellValue('A'.$row, 'CONTOH BARIS');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);

        $examples = [
            ['Rombel', '7A'],
            ['Hari', 'Senin'],
            ['Jam Ke', '1'],
            ['Kode Mapel', 'MAT'],
            ['Mapel', 'Matematika'],
            ['Guru', 'Nama Guru sesuai REF_GURU'],
            ['Ruang', '701'],
            ['Aktif', '1'],
            ['Catatan', 'Opsional'],
        ];

        foreach ($examples as [$label, $value]) {
            $row++;
            $sheet->setCellValue('A'.$row, $label);
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue('B'.$row, $value);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        }

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(100);
    }

    private function buildStudyGroupReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Rombel', 'Kode', 'Ruang'], [18, 18, 16]);

        $row = 2;
        StudyGroup::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->academicYear->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'code', 'room'])
            ->each(function (StudyGroup $group) use ($sheet, &$row) {
                $this->writeRow($sheet, $row++, [$group->name, $group->code, $group->room]);
            });

        $sheet->freezePane('A2');
    }

    private function buildSubjectReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Kode Mapel', 'Nama Mapel'], [18, 42]);

        $row = 2;
        Subject::query()
            ->where(function ($q) {
                $q->whereNull('school_id')->orWhere('school_id', $this->school->id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->each(function (Subject $subject) use ($sheet, &$row) {
                $this->writeRow($sheet, $row++, [$subject->code, $subject->name]);
            });

        $sheet->freezePane('A2');
    }

    private function buildTeacherReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Nama Guru', 'Email', 'Username'], [34, 34, 24]);

        $ids = app(TeacherAvailabilityService::class)->teachersForSchool($this->school->id)->pluck('id');

        $row = 2;
        User::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'email', 'username'])
            ->each(function (User $teacher) use ($sheet, &$row) {
                $this->writeRow($sheet, $row++, [$teacher->name, $teacher->email, $teacher->username]);
            });

        $sheet->freezePane('A2');
    }

    private function buildSlotReference(Worksheet $sheet): void
    {
        $this->writeHeader($sheet, ['Hari', 'Jam Ke', 'Mulai', 'Selesai', 'Jenis'], [14, 10, 10, 10, 18]);

        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $masterDays = app(JadwalGeneratorService::class)->masterSlotsByDay($this->school->id);

        $row = 2;
        foreach ($masterDays as $day => $slots) {
            foreach ($slots as $slot) {
                $this->writeRow($sheet, $row++, [
                    $days[(int) $day] ?? $day,
                    $slot['slot'],
                    substr((string) $slot['start'], 0, 5),
                    substr((string) $slot['end'], 0, 5),
                    $slot['is_break'] ? 'Istirahat' : 'Mengajar',
                ]);
            }
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

        $sheet->getStyle('A1:'.$this->columnLetter(count($headers)).'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::NAVY]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF'.self::BORDER]]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
    }

    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            $sheet->setCellValue($this->columnLetter($index + 1).$row, $value);
        }

        $sheet->getStyle('A'.$row.':'.$this->columnLetter(count($values)).$row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF'.self::BORDER]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
