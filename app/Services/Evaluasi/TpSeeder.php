<?php

namespace App\Services\Evaluasi;

use App\Models\Subject;
use App\Models\TujuanPembelajaran;

/**
 * Seeder for sample Tujuan Pembelajaran.
 * Idempotent: re-running updates instead of duplicating.
 *
 * In production, TPs come from kaprog (Kepala Program) input or imported from
 * kurikulum.repo. This seeder provides a deterministic baseline so the
 * ecosystem is never empty during development.
 */
class TpSeeder
{
    public function seedSample(?string $subjectId = null): int
    {
        $subjects = $subjectId
            ? Subject::where('id', $subjectId)->get()
            : Subject::all();

        if ($subjects->isEmpty()) {
            return 0;
        }

        $count = 0;
        foreach ($subjects as $subject) {
            $tps = $this->buildSampleTps($subject);
            foreach ($tps as $tp) {
                TujuanPembelajaran::updateOrCreate(
                    [
                        'subject_id' => $tp['subject_id'],
                        'kode_tp' => $tp['kode_tp'],
                    ],
                    $tp
                );
                $count++;
            }
        }

        return $count;
    }

    private function buildSampleTps(Subject $subject): array
    {
        $subjectName = strtolower($subject->name ?? 'umum');

        if (str_contains($subjectName, 'matematika') || str_contains($subjectName, 'math')) {
            return $this->matematikaTps($subject->id);
        }
        if (str_contains($subjectName, 'ipa') || str_contains($subjectName, 'sains') || str_contains($subjectName, 'science')) {
            return $this->ipaTps($subject->id);
        }
        if (str_contains($subjectName, 'bahasa') || str_contains($subjectName, 'indonesia') || str_contains($subjectName, 'inggris')) {
            return $this->bahasaTps($subject->id);
        }

        return $this->defaultTps($subject->id);
    }

    private function matematikaTps(string $subjectId): array
    {
        return [
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.01',
                'deskripsi' => 'Peserta didik mampu memahami konsep bilangan bulat dan operasinya.',
                'elemen' => 'Bilangan',
                'fase' => 'E',
                'alokasi_waktu' => 4,
                'urutan' => 1,
            ],
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.02',
                'deskripsi' => 'Peserta didik mampu menerapkan operasi pecahan dalam penyelesaian masalah.',
                'elemen' => 'Bilangan',
                'fase' => 'E',
                'alokasi_waktu' => 4,
                'urutan' => 2,
            ],
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.03',
                'deskripsi' => 'Peserta didik mampu menganalisis persamaan dan pertidaksamaan linear satu variabel.',
                'elemen' => 'Aljabar',
                'fase' => 'E',
                'alokasi_waktu' => 6,
                'urutan' => 3,
            ],
        ];
    }

    private function ipaTps(string $subjectId): array
    {
        return [
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.01',
                'deskripsi' => 'Peserta didik mampu memahami konsep pengukuran dan besaran fisika.',
                'elemen' => 'Pengukuran',
                'fase' => 'E',
                'alokasi_waktu' => 4,
                'urutan' => 1,
            ],
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.02',
                'deskripsi' => 'Peserta didik mampu menganalisis sistem tata surya dan gerak benda langit.',
                'elemen' => 'Bumi dan Antariksa',
                'fase' => 'E',
                'alokasi_waktu' => 6,
                'urutan' => 2,
            ],
        ];
    }

    private function bahasaTps(string $subjectId): array
    {
        return [
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.01',
                'deskripsi' => 'Peserta didik mampu mengidentifikasi informasi dalam teks eksposisi.',
                'elemen' => 'Membaca',
                'fase' => 'E',
                'alokasi_waktu' => 4,
                'urutan' => 1,
            ],
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.02',
                'deskripsi' => 'Peserta didik mampu menulis teks argumentasi dengan struktur yang tepat.',
                'elemen' => 'Menulis',
                'fase' => 'E',
                'alokasi_waktu' => 6,
                'urutan' => 2,
            ],
        ];
    }

    private function defaultTps(string $subjectId): array
    {
        return [
            [
                'subject_id' => $subjectId,
                'kode_tp' => 'TP.01',
                'deskripsi' => 'Peserta didik mampu memahami konsep dasar '.($subject->name ?? 'mata pelajaran').'.',
                'elemen' => 'Konsep Dasar',
                'fase' => 'E',
                'alokasi_waktu' => 4,
                'urutan' => 1,
            ],
        ];
    }
}
