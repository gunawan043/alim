<?php

namespace App\Exports;

use App\Models\School;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    private string $schoolName = '';

    private string $schoolAddress = '';

    private string $downloadInfo = '';

    public function __construct(?string $schoolId = null)
    {
        if ($schoolId) {
            $school = School::with(['province', 'city', 'district'])->find($schoolId);
            if ($school) {
                $this->schoolName = $school->name;
                $this->schoolAddress = 'Kec. '.($school->district?->name ?? '-').', '
                    .'Kabupaten '.($school->city?->name ?? '-').', '
                    .'Provinsi '.($school->province?->name ?? '-');
            }
        }

        if (! $this->schoolName) {
            $this->schoolName = 'NAMA SEKOLAH';
            $this->schoolAddress = 'Kec. ..., Kabupaten/Kota ..., Provinsi ...';
        }

        $user = Auth::user();
        $name = $user ? $user->name : '-';
        if ($user && $user->email) {
            $name .= " ({$user->email})";
        }
        $this->downloadInfo = 'Tanggal Unduh: '.now()->format('Y-m-d H:i:s').'    Pengunduh: '.$name;
    }

    public function title(): string
    {
        return 'Template Import Santri';
    }

    public function headings(): array
    {
        return [
            ['No', 'Nama', 'NIPD', 'JK', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'NIK', 'Agama', 'Alamat',
                'RT', 'RW', 'Dusun', 'Kelurahan', 'Kecamatan', 'Kode Pos', 'Jenis Tinggal', 'Alat Transportasi',
                'Telepon', 'HP', 'E-Mail', 'SKHUN', 'Penerima KPS', 'No. KPS',
                'Nama Ayah', 'Tahun Lahir', 'Jenjang Pendidikan', 'Pekerjaan', 'Penghasilan', 'NIK',
                'Nama Ibu', 'Tahun Lahir', 'Jenjang Pendidikan', 'Pekerjaan', 'Penghasilan', 'NIK',
                'Nama Wali', 'Tahun Lahir', 'Jenjang Pendidikan', 'Pekerjaan', 'Penghasilan', 'NIK',
                'No Peserta Ujian Nasional', 'No Seri Ijazah', 'Penerima KIP', 'Nomor KIP',
                'Nama di KIP', 'Nomor KKS', 'No Registrasi Akta Lahir', 'Bank', 'Nomor Rekening Bank',
                'Rekening Atas Nama', 'Layak PIP', 'Alasan Layak PIP', 'Kebutuhan Khusus', 'Sekolah Asal',
                'Anak ke-berapa', 'Lintang', 'Bujur', 'No KK', 'Berat Badan', 'Tinggi Badan',
                'Lingkar Kepala', 'Jml Saudara Kandung', 'Jarak Rumah ke Sekolah (KM)'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5, 'B' => 22, 'C' => 10, 'D' => 5, 'E' => 14,
            'F' => 16, 'G' => 12, 'H' => 20, 'I' => 9, 'J' => 26,
            'K' => 5, 'L' => 5, 'M' => 16, 'N' => 16, 'O' => 16,
            'P' => 10, 'Q' => 14, 'R' => 14, 'S' => 16, 'T' => 16,
            'U' => 24, 'V' => 10, 'W' => 12, 'X' => 14,
            'Y' => 20, 'Z' => 14, 'AA' => 16, 'AB' => 20, 'AC' => 14, 'AD' => 20,
            'AE' => 20, 'AF' => 14, 'AG' => 16, 'AH' => 22, 'AI' => 14, 'AJ' => 20,
            'AK' => 20, 'AL' => 14, 'AM' => 16, 'AN' => 20, 'AO' => 14, 'AP' => 20,
            'AQ' => 20, 'AR' => 22, 'AS' => 22, 'AT' => 12,
            'AU' => 16, 'AV' => 22, 'AW' => 14, 'AX' => 22, 'AY' => 14, 'AZ' => 18,
            'BA' => 22, 'BB' => 12, 'BC' => 20, 'BD' => 14, 'BE' => 22,
            'BF' => 14, 'BG' => 14, 'BH' => 14, 'BI' => 20, 'BJ' => 10,
            'BK' => 10,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $totalCols = 64;
        $lastCol = Coordinate::stringFromColumnIndex($totalCols);
        $highest = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // Info rows di baris 1, 2, 3
        $sheet
            ->insertNewRowBefore(1, 3)
            ->setCellValue('A1', $this->schoolName)
            ->setCellValue('A2', $this->schoolAddress)
            ->setCellValue('A3', $this->downloadInfo);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP',
            'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK'];

        foreach ($cols as $c) {
            $sheet->mergeCells("{$c}4:{$c}5");
        }

        return [
            // Info rows
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            // Heading row (merged with row 5)
            4 => [
                'font' => ['bold' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            ],
        ];
    }
}
