<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\GtkCrossUnitAssignment;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Services\Documents\SchoolLeaderService;
use App\Services\SchoolGroupService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template import SK & Matriks Pembagian Tugas.
 */
class InstitutionDecreeTemplateExport
{
    private const NAVY = '1E3A5F';

    private const BLUE = '0590D6';

    private const LIGHT = 'DBEAFE';

    private const GRAY = 'F1F5F9';

    private const BORDER = 'CBD5E1';

    private School $school;

    private ?AcademicYear $academicYear;

    public function __construct(School $school, ?AcademicYear $academicYear = null)
    {
        $this->school = $school;
        $this->academicYear = $academicYear ?? AcademicYear::active()->first();
    }

    public function download(string $filename = 'template_import_sk_matriks.xlsx')
    {
        $spreadsheet = new Spreadsheet;

        $this->buildSkSheet($spreadsheet->getActiveSheet());
        $this->buildMatrixSheet($spreadsheet->createSheet()->setTitle('MATRIKS'));
        $this->buildTaskSheet($spreadsheet->createSheet()->setTitle('TUGAS'));
        $this->buildGuideSheet($spreadsheet->createSheet()->setTitle('PETUNJUK'));
        $this->buildTeacherReference($spreadsheet->createSheet()->setTitle('REF_GTK'));
        $this->buildSubjectReference($spreadsheet->createSheet()->setTitle('REF_MAPEL'));
        $this->buildGroupReference($spreadsheet->createSheet()->setTitle('REF_ROMBEL'));

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = storage_path("app/templates/{$filename}");
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildSkSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('SK');

        $headers = [
            'Nomor SK *',
            'Jenis SK',
            'Judul *',
            'Deskripsi',
            'Tahun Ajaran',
            'Tanggal Ditetapkan *',
            'Tanggal Efektif',
            'Tanggal Berakhir',
            'Penandatangan NUPY',
            'Penandatangan Nama',
            'Jabatan Penandatangan',
            'Status',
        ];

        $this->writeHeader($sheet, $headers, [28, 22, 42, 36, 14, 18, 18, 18, 18, 30, 24, 12]);

        $leader = app(SchoolLeaderService::class)->forSchool($this->school);

        $defaults = [
            '',
            'SK Pembagian Tugas',
            '',
            '',
            $this->academicYear?->name,
            now()->format('Y-m-d'),
            now()->format('Y-m-d'),
            '',
            $leader['nupy'] ?? '',
            $leader['display_name'] ?? '',
            'Kepala Sekolah',
            'draft',
        ];

        $this->writeRow($sheet, 2, $defaults, self::GRAY);
        $sheet->getStyle('C2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.self::LIGHT);
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.self::LIGHT);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:L1');
        $this->addNote($sheet, 3, 'Baris 2 sudah berisi nilai awal. Isi Nomor SK & Judul, lalu tambahkan baris baru untuk SK berikutnya. Baris tanpa Nomor SK & Judul akan dilewati.');
    }

    private function buildMatrixSheet(Worksheet $sheet): void
    {
        $headers = [
            'Nomor SK *',
            'NUPY',
            'Nama Guru *',
            'Kode Mapel',
            'Mapel *',
            'Kelas',
            'Rombel *',
            'JP *',
            'Koordinator',
        ];

        $this->writeHeader($sheet, $headers, [28, 18, 34, 14, 30, 12, 14, 10, 14]);

        $this->writeRow($sheet, 2, ['', '', '', 'MAT', 'Matematika', '7', '7A', 6, ''], self::GRAY);
        $sheet->getStyle('A2:I2')->getFont()->setItalic(true);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:I1');
        $this->addNote($sheet, 3, 'Satu baris = satu guru + satu mapel + satu rombel + JP. Hapus baris contoh bila tidak dipakai. NUPY lebih aman daripada nama.');
    }

    private function buildTaskSheet(Worksheet $sheet): void
    {
        $headers = [
            'Nomor SK *',
            'NUPY',
            'Nama Guru *',
            'Nama Tugas *',
            'Kode Tugas',
            'Rombel',
            'JP',
        ];

        $this->writeHeader($sheet, $headers, [28, 18, 34, 34, 20, 14, 10]);

        $this->writeRow($sheet, 2, ['', '', '', 'Wali Kelas', 'wali-kelas', '7A', 12], self::GRAY);
        $sheet->getStyle('A2:G2')->getFont()->setItalic(true);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:G1');
        $this->addNote($sheet, 3, 'Satu baris = satu tugas tambahan. Isi Kode Tugas bila ingin terhubung ke master jenis tugas (mis. wali-kelas).');
    }

    private function buildGuideSheet(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'PETUNJUK IMPORT SK & MATRIKS PEMBAGIAN TUGAS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $lines = [
            ['1.', 'Isi sheet SK. Satu baris = satu SK. Nomor SK & Judul wajib. Status: draft / active / archived.'],
            ['2.', 'Isi sheet MATRIKS. Satu baris = guru + mapel + rombel + JP. Nomor SK harus sama dengan sheet SK.'],
            ['3.', 'Isi sheet TUGAS bila ada tugas tambahan (Wali Kelas, Koordinator, dsb). Boleh dikosongkan.'],
            ['4.', 'Gunakan sheet REF_GTK, REF_MAPEL, dan REF_ROMBEL sebagai acuan penulisan nama. NUPY lebih aman.'],
            ['5.', 'Satu mapel hanya boleh dipegang satu guru per rombel. Pengecualian: Tahfidz/Hadits.'],
            ['6.', 'Jika Nomor SK sudah terdaftar, aktifkan opsi "Perbarui SK yang sudah ada" saat mengunggah.'],
            ['7.', 'Guru, mapel, rombel, dan tahun ajaran harus sudah ada di master. Baris yang tidak cocok akan dilaporkan.'],
            ['8.', 'Tanggal menerima format YYYY-MM-DD atau DD/MM/YYYY.'],
            ['9.', 'Status default draft. Ubah ke active bila SK langsung berlaku.'],
            ['10.', 'Hapus baris contoh (huruf miring abu-abu) sebelum mengunggah.'],
        ];

        $row = 3;
        foreach ($lines as [$number, $text]) {
            $sheet->setCellValue('A'.$row, $number);
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue('B'.$row, $text);
            $sheet->getStyle("A{$row}:F{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("A{$row}:F{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(30);
            $row++;
        }

        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue('A'.$row, 'CONTOH PENGISIAN');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);

        $examples = [
            ['SK', 'Nomor SK: 001/SK/MTs/2026 | Judul: Pembagian Tugas Mengajar | TA: '.($this->academicYear?->name ?? '2026/2027')],
            ['MATRIKS', 'Nama Guru: Ahmad Fauzi | Mapel: Matematika | Rombel: 7A | JP: 6'],
            ['TUGAS', 'Nama Guru: Ahmad Fauzi | Nama Tugas: Wali Kelas | Kode: wali-kelas | Rombel: 7A | JP: 12'],
        ];

        foreach ($examples as [$sheetName, $text]) {
            $row++;
            $sheet->setCellValue('A'.$row, $sheetName);
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue('B'.$row, $text);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        }

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(110);
    }

    private function buildTeacherReference(Worksheet $sheet): void
    {
        $headers = ['NUPY', 'Nama GTK', 'Jabatan'];
        $this->writeHeader($sheet, $headers, [20, 40, 34]);

        $userIds = collect(SchoolGroupService::userIdsForSchool($this->school->id))
            ->merge(GtkCrossUnitAssignment::where('school_id', $this->school->id)
                ->when($this->academicYear, fn ($q) => $q->where('academic_year_id', $this->academicYear->id))
                ->active()
                ->pluck('user_id'))
            ->merge(GtkEmployment::where('school_id', $this->school->id)->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        $users = User::whereIn('id', $userIds)->orderBy('name')->get();
        $employments = GtkEmployment::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $workUnitId = $this->school->work_unit_id;
        $workUnits = GtkWorkUnit::with('position:id,name')
            ->whereIn('user_id', $userIds)
            ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->get()
            ->keyBy('user_id');

        $row = 2;
        foreach ($users as $user) {
            $employment = $employments->get($user->id);
            $workUnit = $workUnits->get($user->id);
            $position = $workUnit?->position?->name ?? $workUnit?->jabatan ?? $employment?->jabatan ?? 'GTK';

            $this->writeRow($sheet, $row, [(string) ($employment?->nupy ?? ''), $user->name, $position], $row % 2 === 0 ? self::GRAY : 'FFFFFF');
            $row++;
        }

        $sheet->freezePane('A2');
    }

    private function buildSubjectReference(Worksheet $sheet): void
    {
        $headers = ['Kode Mapel', 'Nama Mapel'];
        $this->writeHeader($sheet, $headers, [20, 46]);

        $row = 2;
        $subjects = Subject::where(fn ($q) => $q->where('school_id', $this->school->id)->orWhereNull('school_id'))
            ->orderBy('name')
            ->get();

        foreach ($subjects as $subject) {
            $this->writeRow($sheet, $row, [$subject->code, $subject->name], $row % 2 === 0 ? self::GRAY : 'FFFFFF');
            $row++;
        }

        $sheet->freezePane('A2');
    }

    private function buildGroupReference(Worksheet $sheet): void
    {
        $headers = ['Rombel', 'Kode', 'Kelas', 'Tahun Ajaran'];
        $this->writeHeader($sheet, $headers, [18, 16, 20, 18]);

        $row = 2;
        $groups = StudyGroup::with('gradeLevel:id,name,level')
            ->where('school_id', $this->school->id)
            ->when($this->academicYear, fn ($q) => $q->where('academic_year_id', $this->academicYear->id))
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($group) => [$group->gradeLevel->level ?? 0, $group->name]);

        foreach ($groups as $group) {
            $this->writeRow($sheet, $row, [
                $group->name,
                $group->code,
                $group->gradeLevel->name ?? '',
                $this->academicYear?->name,
            ], $row % 2 === 0 ? self::GRAY : 'FFFFFF');
            $row++;
        }

        $sheet->freezePane('A2');
    }

    // ─────────────────────────────────────────────────────────────
    // STYLE
    // ─────────────────────────────────────────────────────────────

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

    private function writeRow(Worksheet $sheet, int $row, array $values, string $background = 'FFFFFF'): void
    {
        foreach ($values as $index => $value) {
            $sheet->setCellValue($this->columnLetter($index + 1).$row, $value);
        }

        $last = $this->columnLetter(count($values));
        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font' => ['size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$background]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF'.self::BORDER]]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function addNote(Worksheet $sheet, int $row, string $note): void
    {
        $sheet->mergeCells("A{$row}:L{$row}");
        $sheet->setCellValue('A'.$row, $note);
        $sheet->getStyle('A'.$row)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::LIGHT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
