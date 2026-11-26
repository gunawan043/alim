<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\User;
use App\Services\TahfidzAccess;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Template impor halaqah (.xlsx).
 *
 * Sheet "Halaqah" berisi judul kolom + satu contoh baris.
 * Sheet "Referensi" memuat daftar kelas, guru tahfidz, dan santri sekolah
 * agar pengisi berkas tidak menebak nama.
 */
class TahfidzHalaqahTemplateExport
{
    private const NAVY = '1E3A5F';

    private const HEADER_BG = 'DBEAFE';

    public function __construct(
        private readonly School $school,
        private readonly ?AcademicYear $academicYear,
    ) {}

    public function download(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'halaqah-template-').'.xlsx';

        $writer = new Xlsx($this->build());
        $writer->save($path);

        $filename = 'template-impor-halaqah-'.str($this->school->name)->slug().'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function build(): Spreadsheet
    {
        $book = new Spreadsheet();

        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Halaqah');

        $headings = ['nama_halaqah', 'kelas', 'guru', 'nis', 'nama_santri'];

        foreach ($headings as $index => $heading) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column.'1', $heading);
            $sheet->getStyle($column.'1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($column.'1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF'.self::NAVY);
            $sheet->getColumnDimension($column)->setWidth(22);
        }

        // Contoh baris: dua santri pada satu halaqah yang sama.
        $sheet->fromArray([
            ['Halaqah Umar bin Khattab', 'Kelas 1', '', '', ''],
        ], null, 'A2');

        $sheet->getStyle('A1:E1')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('G1', 'Cara mengisi');
        $sheet->getStyle('G1')->getFont()->setBold(true);

        $notes = [
            'Satu baris = satu santri.',
            'Baris berikutnya dengan nama_halaqah sama akan menambah santri ke halaqah itu.',
            'Baris halaqah tanpa anggota tetap sah — kosongkan nis dan nama_santri.',
            'Kolom guru: nama persis seperti pada sheet Referensi.',
            'Kolom kelas: nama kelas (mis. "Kelas 1") atau angka tingkat (mis. 1).',
            'Santri dikenali lewat NIS. Bila NIS kosong, dicari dari nama_santri.',
            'Halaqah dengan nama dan tahun ajaran sama akan DIPERBARUI, bukan diduplikasi.',
            'Anggota lama tidak dikeluarkan; berkas hanya menambah anggota.',
        ];

        foreach ($notes as $i => $note) {
            $sheet->setCellValue('G'.($i + 2), '• '.$note);
            $sheet->getStyle('G'.($i + 2))->getFont()->setSize(9);
        }

        $sheet->getColumnDimension('G')->setWidth(70);

        $this->referenceSheet($book);

        return $book;
    }

    private function referenceSheet(Spreadsheet $book): void
    {
        $ref = $book->createSheet();
        $ref->setTitle('Referensi');

        $yearLabel = $this->academicYear
            ? $this->academicYear->name.' — '.ucfirst($this->academicYear->semester)
            : '—';

        $ref->setCellValue('A1', 'Satuan pendidikan: '.$this->school->name);
        $ref->setCellValue('A2', 'Tahun ajaran: '.$yearLabel);
        $ref->getStyle('A1:A2')->getFont()->setBold(true);

        // Kelas
        $ref->setCellValue('A4', 'Kelas');
        $ref->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.self::HEADER_BG);

        $grades = GradeLevel::query()
            ->where('school_id', $this->school->id)
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['name', 'level']);

        $row = 5;
        foreach ($grades as $grade) {
            $ref->setCellValue('A'.$row, $grade->name);
            $ref->setCellValue('B'.$row, $grade->level);
            $row++;
        }

        // Guru tahfidz
        $teacherRow = 4;
        $ref->setCellValue('D4', 'Guru Tahfidz');
        $ref->getStyle('D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.self::HEADER_BG);

        $teachers = User::query()
            ->whereHas('employment', fn ($q) => $q->where('school_id', $this->school->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (User $user) => app(TahfidzAccess::class)->isTahfidzTeacher($user))
            ->values();

        $teacherRow = 5;
        foreach ($teachers as $teacher) {
            $ref->setCellValue('D'.$teacherRow, $teacher->name);
            $teacherRow++;
        }

        $ref->getColumnDimension('A')->setWidth(22);
        $ref->getColumnDimension('B')->setWidth(8);
        $ref->getColumnDimension('D')->setWidth(32);
    }
}
