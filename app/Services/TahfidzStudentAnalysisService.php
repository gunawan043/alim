<?php

namespace App\Services;

use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use Carbon\Carbon;

class TahfidzStudentAnalysisService
{
    public function analyze(string $studentId, string $academicYearId): array
    {
        $setorans = TahfidzSetoran::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('verification_status', 'verified')
            ->orderByDesc('setoran_date')
            ->orderByDesc('created_at')
            ->get();

        $last = $setorans->first();
        $daysSinceLast = $last?->setoran_date ? (int) Carbon::parse($last->setoran_date)->diffInDays(now()) : null;
        $count7 = $setorans->filter(fn ($row) => $row->setoran_date && Carbon::parse($row->setoran_date)->gte(now()->subDays(7)))->count();
        $count30 = $setorans->filter(fn ($row) => $row->setoran_date && Carbon::parse($row->setoran_date)->gte(now()->subDays(30)))->count();
        $scored = $setorans->whereNotNull('nilai_setoran');
        $average = $scored->isNotEmpty() ? round((float) $scored->avg('nilai_setoran'), 2) : null;
        $recentAverage = $scored->take(5)->isNotEmpty() ? round((float) $scored->take(5)->avg('nilai_setoran'), 2) : null;
        $previousAverage = $scored->slice(5, 5)->isNotEmpty() ? round((float) $scored->slice(5, 5)->avg('nilai_setoran'), 2) : null;
        $lowScores = $scored->filter(fn ($row) => (float) $row->nilai_setoran < 70)->count();
        $repeatCount = $setorans->where('status', 'ulang')->count();

        $progress = TahfidzJuzProgress::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->get();
        $juzCompleted = $progress->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count();
        $recap = TahfidzProgressRecap::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->orderByDesc('semester')
            ->first();
        $targetPercent = $recap?->pencapaian_target_persen !== null ? (float) $recap->pencapaian_target_persen : null;

        $lastMutabaahDate = TahfidzMutabaah::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->max('record_date');
        $mutabaahDays = $lastMutabaahDate ? (int) Carbon::parse($lastMutabaahDate)->diffInDays(now()) : null;

        $score = 0;
        $reasons = [];
        $recommendations = [];

        if (! $last) {
            $score += 40;
            $reasons[] = 'Belum ada setoran terverifikasi.';
            $recommendations[] = 'Jadwalkan setoran awal dan dampingi santri secara intensif.';
        } elseif ($daysSinceLast > 14) {
            $score += 30;
            $reasons[] = 'Tidak setoran '.$daysSinceLast.' hari.';
            $recommendations[] = 'Hubungi wali santri dan jadwalkan setoran susulan.';
        } elseif ($daysSinceLast > 7) {
            $score += 15;
            $reasons[] = 'Setoran terakhir '.$daysSinceLast.' hari lalu.';
            $recommendations[] = 'Ingatkan jadwal setoran dan cek kehadiran halaqah.';
        }

        if ($count30 < 2) {
            $score += 30;
            $reasons[] = 'Frekuensi setoran sangat rendah ('.$count30.' kali/30 hari).';
            $recommendations[] = 'Tetapkan minimal 3 setoran per pekan.';
        } elseif ($count30 < 4) {
            $score += 20;
            $reasons[] = 'Frekuensi setoran rendah ('.$count30.' kali/30 hari).';
            $recommendations[] = 'Tambahkan jadwal setoran dan murajaah mandiri.';
        } elseif ($count30 < 8) {
            $score += 10;
            $reasons[] = 'Frekuensi setoran belum rutin.';
            $recommendations[] = 'Pantau konsistensi setoran setiap pekan.';
        }

        if ($average !== null) {
            if ($average < 60) {
                $score += 30;
                $reasons[] = 'Rata-rata nilai sangat lemah ('.number_format($average, 1).').';
                $recommendations[] = 'Turunkan target sementara dan fokus murajaah dasar.';
            } elseif ($average < 70) {
                $score += 20;
                $reasons[] = 'Rata-rata nilai di bawah 70 ('.number_format($average, 1).').';
                $recommendations[] = 'Adakan bimbingan tambahan dan tikror terbimbing.';
            } elseif ($average < 80) {
                $score += 10;
                $reasons[] = 'Nilai hafalan perlu ditingkatkan.';
                $recommendations[] = 'Perbanyak murajaah dan tasmi’ pekanan.';
            }
        }

        if ($recentAverage !== null && $previousAverage !== null && $recentAverage < ($previousAverage - 5)) {
            $score += 10;
            $reasons[] = 'Nilai 5 setoran terakhir menurun.';
            $recommendations[] = 'Cek beban belajar dan berikan motivasi/remedial.';
        }

        if ($targetPercent !== null && $targetPercent < 50) {
            $score += 15;
            $reasons[] = 'Capaian target baru '.number_format($targetPercent, 1).'%.';
            $recommendations[] = 'Sesuaikan target dengan kemampuan dan susun rencana kejar.';
        }

        if ($mutabaahDays === null) {
            $score += 10;
            $reasons[] = 'Belum ada mutaba’ah harian.';
            $recommendations[] = 'Aktifkan mutaba’ah dan libatkan wali/musyrif.';
        } elseif ($mutabaahDays > 7) {
            $score += 10;
            $reasons[] = 'Mutaba’ah terakhir '.$mutabaahDays.' hari lalu.';
            $recommendations[] = 'Ingatkan pengisian mutaba’ah harian.';
        }

        if ($lowScores > 0) {
            $reasons[] = $lowScores.' setoran bernilai di bawah 70.';
        }
        if ($repeatCount > 0) {
            $reasons[] = $repeatCount.' setoran harus diulang.';
        }

        $score = min(100, $score);
        [$level, $label, $color] = match (true) {
            $score >= 60 => ['kritis', 'Kritis', 'danger'],
            $score >= 30 => ['perlu_perhatian', 'Perlu Perhatian', 'warning'],
            default => ['baik', 'Baik', 'success'],
        };

        if (empty($recommendations)) {
            $recommendations[] = 'Pertahankan rutinitas dan tingkatkan kualitas hafalan.';
        }

        return [
            'risk_score' => $score,
            'risk_level' => $level,
            'risk_label' => $label,
            'risk_color' => $color,
            'last_setoran' => $last?->setoran_date,
            'days_since_last' => $daysSinceLast,
            'count_7' => $count7,
            'count_30' => $count30,
            'average' => $average,
            'recent_average' => $recentAverage,
            'previous_average' => $previousAverage,
            'low_scores' => $lowScores,
            'repeat_count' => $repeatCount,
            'juz_completed' => $juzCompleted,
            'target_percent' => $targetPercent,
            'last_mutabaah' => $lastMutabaahDate,
            'mutabaah_days' => $mutabaahDays,
            'reasons' => array_values(array_unique($reasons)),
            'recommendations' => array_values(array_unique($recommendations)),
        ];
    }
}
