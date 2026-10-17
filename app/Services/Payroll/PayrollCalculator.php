<?php

namespace App\Services\Payroll;

use App\Models\AbsensiGtk;
use App\Models\GtkProfile;
use App\Models\PayrollSetting;
use App\Models\Payroll\Masakerja;
use App\Models\Payroll\Pendidikan;
use App\Models\Payroll\PersentaseGaji;
use App\Models\Payroll\SalaryScal;
use App\Models\Payroll\SkalaGajiGuru;
use App\Models\Payroll\SkalaGajiTendik;
use App\Models\Payroll\Tunjangan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mesin perhitungan gaji GTK.
 *
 * Urutan:
 * 1. Gaji pokok dari skala gaji guru/tendik (masa kerja + level + golongan pendidikan).
 * 2. Fallback salary_scal (pendidikan + masa kerja).
 * 3. Tunjangan tetap + tunjangan kehadiran.
 * 4. Potongan keterlambatan, alpa, dan izin (sesuai pengaturan).
 */
class PayrollCalculator
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(GtkProfile $gtk, int $month, int $year): array
    {
        $gtk->loadMissing(['user', 'latestEmployment']);
        $user = $gtk->user;
        $employment = $gtk->latestEmployment;

        if (! $user) {
            return $this->fail(['Akun pengguna GTK tidak ditemukan.']);
        }

        if (! $employment) {
            return $this->fail(['Data kepegawaian GTK belum diisi.']);
        }

        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $years = $employment->tmt
            ? max(0, (int) $employment->tmt->diffInYears($periodEnd))
            : 0;

        $isGuru = Str::contains(strtolower((string) $employment->jenis_gtk), 'guru');

        $masaKerja = Masakerja::query()
            ->where('masa_kerja', '<=', $years)
            ->orderByDesc('masa_kerja')
            ->first();

        $level = $masaKerja?->level ?? max(1, min(5, intdiv($years, 2) + 1));

        $strata = $this->highestEducation($user->id);
        $pendidikan = $strata
            ? Pendidikan::query()->whereRaw('LOWER(Strata) = ?', [strtolower($strata)])->first()
            : null;

        $golongan = $pendidikan?->Golongan ?? $this->guessGolongan($strata);

        [$base, $baseSource] = $this->baseSalary($isGuru, $years, $level, $golongan, $strata);

        if ($base <= 0) {
            return $this->fail([
                'Gaji pokok tidak ditemukan. Isi skala gaji '.($isGuru ? 'guru' : 'tendik')
                ." untuk masa kerja {$years} tahun, level {$level}, golongan {$golongan}.",
            ], compact('years', 'level', 'golongan', 'strata'));
        }

        $attendance = $this->attendanceSummary($gtk->id, $month, $year);

        [$tunjangan, $potongan] = $this->components($base, $attendance);

        $totalTunjangan = array_sum(array_column($tunjangan, 'nominal'));
        $totalPotongan = array_sum(array_column($potongan, 'nominal'));

        return [
            'ok' => true,
            'errors' => [],
            'gaji_pokok' => round($base, 2),
            'tunjangan' => $tunjangan,
            'potongan' => $potongan,
            'total_tunjangan' => round($totalTunjangan, 2),
            'total_potongan' => round($totalPotongan, 2),
            'gaji_bersih' => round($base + $totalTunjangan - $totalPotongan, 2),
            'meta' => [
                'jenis' => $isGuru ? 'guru' : 'tendik',
                'masa_kerja' => $years,
                'level' => $level,
                'golongan' => $golongan,
                'strata' => $strata,
                'sumber_gaji_pokok' => $baseSource,
                'kehadiran' => $attendance,
            ],
        ];
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function baseSalary(bool $isGuru, int $years, int $level, int $golongan, ?string $strata): array
    {
        $model = $isGuru ? SkalaGajiGuru::class : SkalaGajiTendik::class;
        $column = 'golongan_'.max(1, min(5, $golongan));

        $scale = $model::query()
            ->where('masa_kerja', '<=', $years)
            ->where('level', $level)
            ->orderByDesc('masa_kerja')
            ->first();

        $base = (float) ($scale?->{$column} ?? 0);

        if ($base <= 0 && $strata) {
            $fallback = SalaryScal::query()
                ->whereRaw('LOWER(pendidikan) = ?', [strtolower($strata)])
                ->where('masa_kerja', '<=', $years)
                ->orderByDesc('masa_kerja')
                ->first();
            $base = (float) ($fallback?->gaji ?? 0);

            if ($base > 0) {
                return [$this->applyPercentage($base, $golongan), 'salary_scal'];
            }
        }

        return [$base > 0 ? $this->applyPercentage($base, $golongan) : 0, 'skala_gaji'];
    }

    private function applyPercentage(float $base, int $golongan): float
    {
        if (! PayrollSetting::get('payroll_use_percentage', false)) {
            return $base;
        }

        $percentage = PersentaseGaji::query()->first();
        if (! $percentage) {
            return $base;
        }

        $column = 'persentase_golongan_'.max(1, min(4, $golongan));
        $value = (float) ($percentage->{$column} ?? 100);

        return $value > 0 ? $base * ($value / 100) : $base;
    }

    /**
     * @return array{hadir: int, terlambat_menit: int, alpa: int, izin: int, sakit: int, cuti: int, dinas_luar: int}
     */
    private function attendanceSummary(string $gtkId, int $month, int $year): array
    {
        $base = AbsensiGtk::query()
            ->where('gtk_id', $gtkId)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month);

        return [
            'hadir' => (clone $base)->where('status', 'hadir')->count(),
            'terlambat_menit' => (int) (clone $base)->sum('terlambat_menit'),
            'alpa' => (clone $base)->whereIn('status', ['alpa', 'alpha'])->count(),
            'izin' => (clone $base)->where('status', 'izin')->count(),
            'sakit' => (clone $base)->where('status', 'sakit')->count(),
            'cuti' => (clone $base)->where('status', 'cuti')->count(),
            'dinas_luar' => (clone $base)->where('status', 'dinas_luar')->count(),
        ];
    }

    /**
     * @param  array<string, int>  $attendance
     * @return array{0: array<int, array{jenis: string, nominal: float}>, 1: array<int, array{jenis: string, nominal: float}>}
     */
    private function components(float $base, array $attendance): array
    {
        $tunjangan = [];
        $potongan = [];

        foreach (Tunjangan::where('is_active', true)->orderBy('Kode')->get() as $item) {
            $nominal = (float) $item->Nominal;

            if ($item->is_attendance_based) {
                if ($attendance['hadir'] <= 0) {
                    continue;
                }
                $nominal = $item->per_hari ? $nominal * $attendance['hadir'] : $nominal;
            }

            if ($nominal > 0) {
                $tunjangan[] = ['jenis' => $item->Subjek ?: $item->Kode, 'nominal' => round($nominal, 2)];
            }
        }

        if (PayrollSetting::get('payroll_attendance_allowance_enabled', false)) {
            $perDay = (float) PayrollSetting::get('payroll_attendance_allowance_per_day', 0);
            if ($perDay > 0 && $attendance['hadir'] > 0) {
                $tunjangan[] = [
                    'jenis' => 'Tunjangan Kehadiran',
                    'nominal' => round($perDay * $attendance['hadir'], 2),
                ];
            }
        }

        $lateRate = (float) PayrollSetting::get('payroll_late_deduction_per_minute', 0);
        if ($lateRate > 0 && $attendance['terlambat_menit'] > 0) {
            $potongan[] = [
                'jenis' => 'Potongan Keterlambatan',
                'nominal' => round($lateRate * $attendance['terlambat_menit'], 2),
            ];
        }

        $absentRate = (float) PayrollSetting::get('payroll_absent_deduction_per_day', 0);
        if ($absentRate > 0 && $attendance['alpa'] > 0) {
            $potongan[] = [
                'jenis' => 'Potongan Alpa',
                'nominal' => round($absentRate * $attendance['alpa'], 2),
            ];
        }

        if (PayrollSetting::get('payroll_bpjs_tk_enabled', false)) {
            $rate = (float) PayrollSetting::get('payroll_bpjs_tk_percent', 0);
            if ($rate > 0) {
                $potongan[] = [
                    'jenis' => 'BPJS Ketenagakerjaan',
                    'nominal' => round($base * ($rate / 100), 2),
                ];
            }
        }

        if (PayrollSetting::get('payroll_bpjs_kes_enabled', false)) {
            $rate = (float) PayrollSetting::get('payroll_bpjs_kes_percent', 0);
            if ($rate > 0) {
                $potongan[] = [
                    'jenis' => 'BPJS Kesehatan',
                    'nominal' => round($base * ($rate / 100), 2),
                ];
            }
        }

        $leaveRate = (float) PayrollSetting::get('payroll_leave_deduction_per_day', 0);
        $leaveDays = $attendance['izin'] + $attendance['sakit'];
        if ($leaveRate > 0 && $leaveDays > 0) {
            $potongan[] = [
                'jenis' => 'Potongan Izin/Sakit',
                'nominal' => round($leaveRate * $leaveDays, 2),
            ];
        }

        return [$tunjangan, $potongan];
    }

    private function highestEducation(string $userId): ?string
    {
        $education = \App\Models\GtkEducation::query()
            ->where('user_id', $userId)
            ->orderByDesc('tahun_lulus')
            ->orderByDesc('created_at')
            ->first();

        return $education?->jenjang_pendidikan
            ? strtoupper(trim((string) $education->jenjang_pendidikan))
            : null;
    }

    private function guessGolongan(?string $strata): int
    {
        if (! $strata) {
            return 1;
        }

        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $strata) ?? '');

        return match (true) {
            str_contains($normalized, 'S3') => 5,
            str_contains($normalized, 'S2') => 4,
            str_contains($normalized, 'S1'), str_contains($normalized, 'D4') => 3,
            str_contains($normalized, 'D3'), str_contains($normalized, 'D2') => 2,
            default => 1,
        };
    }

    /**
     * @param  array<int, string>  $errors
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function fail(array $errors, array $meta = []): array
    {
        return [
            'ok' => false,
            'errors' => $errors,
            'gaji_pokok' => 0,
            'tunjangan' => [],
            'potongan' => [],
            'total_tunjangan' => 0,
            'total_potongan' => 0,
            'gaji_bersih' => 0,
            'meta' => $meta,
        ];
    }
}
