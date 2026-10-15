<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lembar soal (naskah cetak untuk santri).
 *
 * Format kop mengikuti naskah madrasah:
 *   SOAL ASESMEN SUMATIF TENGAH SEMESTER GANJIL
 *   TAHUN AJARAN 2026/2027
 *   Mata Pelajaran / Hari-Tanggal / Kelas / Waktu / Guru Pengampu / Nama Peserta Didik
 *   PENANAMAN KARAKTER (3 butir)
 *   A. Pilihlah jawaban …
 *
 * Catatan: lembar ini TIDAK memuat bobot/poin, jenis soal, TP, kunci, maupun
 * pembahasan — hanya nomor, pertanyaan, dan pilihan jawaban. Opsi jawaban
 * mengalir mendatar; hanya turun ke baris berikutnya bila tidak muat.
 */
class LjkSoalSheet
{
    /**
     * @return array<string, mixed>
     */
    public function data(LjkExam $exam): array
    {
        $exam->loadMissing(['subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']);

        $soal = $this->questions($exam);

        return [
            'judul' => $this->judul($exam),
            'mataPelajaran' => (string) ($exam->subject?->name ?? '-'),
            'hariTanggal' => $this->dayDate($exam->tanggal),
            'kelas' => $this->kelasLabel($exam),
            'waktu' => $this->waktuLabel($exam),
            'guruPengampu' => (string) ($exam->adminBook?->teacher?->name ?? '-'),
            'namaPeserta' => '',
            'karakter' => (array) config('ljk.soal.penanaman_karakter', []),
            'instruksi' => (string) config('ljk.soal.instruksi', ''),
            'soal' => $soal,
            'fontStack' => LjkPdfFonts::stack(),
            'fontSize' => (float) config('ljk.soal.font_size', 11.5),
            'faceCss' => LjkPdfFonts::faceCss(),
            'canonical' => app(LjkExamService::class)->usesCanonicalOrder($exam),
        ];
    }

    public function render(LjkExam $exam): string
    {
        return view('ljk.soal-pdf', $this->data($exam))->render();
    }

    /**
     * Daftar soal dari naskah beku — TANPA bobot/poin, jenis, TP, kunci, pembahasan.
     *
     * @return array<int, array{nomor:int, pertanyaan:string, opsi:array<int, array{label:string, teks:string}>}>
     */
    public function questions(LjkExam $exam): array
    {
        if (! $exam->paketSoal) {
            return [];
        }

        return $exam->paketSoal->items()
            ->with(['soal.options'])
            ->orderBy('urutan')
            ->get()
            ->values()
            ->map(function ($item, int $index) {
                $konten = $item->kontenFinal();

                return [
                    'nomor' => (int) ($konten['nomor'] ?? $index + 1),
                    'pertanyaan' => (string) ($konten['pertanyaan'] ?? ''),
                    'opsi' => collect($konten['opsi'] ?? [])->map(fn ($o) => [
                        'label' => (string) ($o['label'] ?? ''),
                        'teks' => (string) ($o['teks'] ?? ''),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string> [judul baris 1, "TAHUN AJARAN …"]
     */
    public function judul(LjkExam $exam): array
    {
        $map = (array) config('ljk.soal.judul', []);
        $jenis = (string) $exam->jenis_ujian;

        $judul = $map[$jenis] ?? 'SOAL ASESMEN';

        if (in_array($jenis, (array) config('ljk.soal.semester_pada_judul', ['sts', 'sas']), true)) {
            $judul .= ' '.mb_strtoupper((string) $exam->semester);
        }

        $tahun = trim((string) ($exam->academicYear?->name ?? ''));

        return [
            $judul,
            $tahun !== '' ? 'TAHUN AJARAN '.$tahun : '',
        ];
    }

    /**
     * "Jum'at, 18 September 2026" (bulan Indonesia).
     */
    public function dayDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'][$date->dayOfWeek] ?? '';
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$date->month] ?? '';

        return trim($hari.', '.$date->day.' '.$bulan.' '.$date->year);
    }

    /**
     * "Sepuluh (X) C & D" — dari tingkat + nama rombel.
     */
    public function kelasLabel(LjkExam $exam): string
    {
        $group = $exam->studyGroup;
        $grade = $group?->gradeLevel;

        $level = $this->gradeLevelNumber($grade?->level, (string) ($grade?->name ?? ''));
        $nama = trim((string) ($group?->name ?? ''));

        if ($level === null) {
            return $nama !== '' ? $nama : '-';
        }

        $kata = $this->numberWord($level);
        $romawi = $this->roman($level);

        return trim($kata.' ('.$romawi.')'.($nama !== '' ? ' '.$nama : ''));
    }

    /**
     * "07.30 – 09.00" (gaya Indonesia) atau '' bila jam belum diisi.
     */
    public function waktuLabel(LjkExam $exam): string
    {
        $mulai = $exam->waktu_mulai?->format('H.i');
        $selesai = $exam->waktu_selesai?->format('H.i');

        return match (true) {
            $mulai !== null && $selesai !== null => $mulai.' – '.$selesai,
            $mulai !== null => $mulai,
            default => '',
        };
    }

    private function gradeLevelNumber(?int $level, string $name): ?int
    {
        if ($level !== null && $level > 0) {
            return $level;
        }

        if (preg_match('/(\d{1,2})/', $name, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\b(X{0,3})(IX|IV|V?I{0,3})\b/i', $name, $m) && ($m[1].$m[2]) !== '') {
            $roman = strtoupper($m[1].$m[2]);
            $map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
                'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12];

            return $map[$roman] ?? null;
        }

        return null;
    }

    private function numberWord(int $level): string
    {
        $words = [
            1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat', 5 => 'Lima', 6 => 'Enam',
            7 => 'Tujuh', 8 => 'Delapan', 9 => 'Sembilan', 10 => 'Sepuluh', 11 => 'Sebelas', 12 => 'Dua Belas',
        ];

        return $words[$level] ?? (string) $level;
    }

    private function roman(int $level): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$level] ?? (string) $level;
    }
}
