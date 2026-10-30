<?php

namespace App\Services\Dashboard;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agregator analitik dashboard lintas jabatan.
 *
 * Semua query dibungkus try/catch per blok supaya satu tabel yang belum
 * dimigrasi tidak mematikan seluruh dashboard. Nilai yang tidak dapat
 * dihitung dikembalikan null, bukan 0.
 */
class DashboardAnalyticsService
{
    public function __construct(private DashboardContextService $context) {}

    /**
     * Paket data untuk dashboard jabatan.
     *
     * @param  array<string, mixed>  $filters  hasil DashboardContextService::filters()
     * @return array<string, mixed>
     */
    public function build(string $roleSlug, array $filters, ?User $user = null): array
    {
        $schoolIds = $filters['school_ids'] ?? [];
        $academicYearId = $filters['academic_year_id'] ?? null;

        $students = $this->studentStats($schoolIds, $academicYearId, $filters);
        $attendance = $this->attendanceTrend($schoolIds);
        $mutations = $this->mutationTrend($schoolIds);

        return [
            'students' => $students,
            'distribution' => $this->distribution($schoolIds, $academicYearId, $filters),
            'attendance' => $attendance,
            'mutations' => $mutations,
            'gtk' => $this->gtkStats($schoolIds),
            'rombel' => $this->rombelStats($schoolIds, $academicYearId, $filters),
            'academic_health' => $this->academicHealth($schoolIds, $academicYearId),
            'finance' => $this->financeStats($schoolIds),
            'boarding' => $this->boardingStats($schoolIds),
            'tahfidz' => $this->tahfidzStats($schoolIds),
            'health' => $this->healthStats($schoolIds),
            'sdm' => $this->sdmStats($schoolIds),
            'sarpras' => $this->sarprasStats($schoolIds),
            'system' => $this->systemStats(),
            'teacher' => $this->teacherStats($schoolIds, $academicYearId, $user?->id),
            'homeroom' => $this->homeroomStats($schoolIds, $academicYearId, $filters, $user?->id),
            'findings' => [],
            'kpis' => [],
            'series' => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // SANTRI & POPULASI
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{total: ?int, male: ?int, female: ?int, new: ?int, entry_this_month: ?int, previous_total: ?int, growth_rate: ?float, source: string}
     */
    public function studentStats(array $schoolIds, ?string $academicYearId, array $filters): array
    {
        $empty = [
            'total' => null, 'male' => null, 'female' => null, 'new' => null,
            'entry_this_month' => null, 'previous_total' => null, 'growth_rate' => null,
            'source' => 'students',
        ];

        try {
            $schoolId = $schoolIds[0] ?? null;
            $base = Student::query()
                ->where('status', 'active')
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId));

            $total = (clone $base)->count();
            $male = (clone $base)->where('gender', 'L')->count();
            $female = (clone $base)->where('gender', 'P')->count();

            $year = $filters['academic_year'] ?? null;
            $yearStart = $year?->start_date;
            $yearEnd = $year?->end_date;

            $new = $yearStart
                ? (clone $base)->whereBetween('entry_date', [$yearStart->toDateString(), ($yearEnd ?? now())->toDateString()])->count()
                : null;

            // Santri baru pada tahun ajaran sebelumnya sebagai pembanding.
            $previousTotal = null;
            $previousNew = null;

            if ($yearStart) {
                $previousYearStart = AcademicYear::query()
                    ->where('start_date', '<', $yearStart)
                    ->orderByDesc('start_date')
                    ->value('start_date');

                if ($previousYearStart) {
                    $previousNew = Student::query()
                        ->where('status', 'active')
                        ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                        ->whereBetween('entry_date', [$previousYearStart, $yearStart->copy()->subDay()->toDateString()])
                        ->count();
                }
            }

            return [
                'total' => $total,
                'male' => $male,
                'female' => $female,
                'new' => $new,
                'entry_this_month' => (clone $base)
                    ->whereMonth('entry_date', now()->month)
                    ->whereYear('entry_date', now()->year)
                    ->count(),
                'previous_total' => $previousTotal,
                'previous_new' => $previousNew,
                'growth_rate' => ($previousNew !== null && $previousNew > 0 && $new !== null)
                    ? round((($new - $previousNew) / $previousNew) * 100, 2)
                    : null,
                'source' => 'students',
            ];
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::studentStats — '.$e->getMessage());

            return $empty;
        }
    }

    /**
     * Distribusi santri per jenjang, kelas, dan gender.
     *
     * @return array{by_grade: array<int, array{name: string, total: int}>, by_class: array<int, array{name: string, total: int, capacity: ?int}>, gender: array<string, int>}
     */
    public function distribution(array $schoolIds, ?string $academicYearId, array $filters): array
    {
        $result = ['by_grade' => [], 'by_class' => [], 'gender' => ['L' => 0, 'P' => 0]];

        try {
            $gradeId = $filters['grade_level_id'] ?? null;

            $rows = DB::table('student_class_histories as h')
                ->join('study_groups as sg', 'sg.id', '=', 'h.study_group_id')
                ->join('grade_levels as gl', 'gl.id', '=', 'sg.grade_level_id')
                ->join('students as s', 's.id', '=', 'h.student_id')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('sg.school_id', $schoolIds))
                ->when($academicYearId, fn ($q) => $q->where('h.academic_year_id', $academicYearId))
                ->when($gradeId, fn ($q) => $q->where('sg.grade_level_id', $gradeId))
                ->where('h.is_active', 1)
                ->where('s.status', 'active')
                ->selectRaw('gl.name as grade_name, gl.level as grade_level, sg.name as class_name, sg.capacity, h.student_id, s.gender')
                ->get();

            $result['by_grade'] = $rows->groupBy('grade_name')
                ->map(fn ($group, $name) => [
                    'name' => $name,
                    'total' => $group->pluck('student_id')->unique()->count(),
                ])
                ->sortByDesc('total')
                ->values()
                ->all();

            $result['by_class'] = $rows->groupBy('class_name')
                ->map(fn ($group, $name) => [
                    'name' => $name,
                    'total' => $group->pluck('student_id')->unique()->count(),
                    'capacity' => $group->first()->capacity,
                ])
                ->sortByDesc('total')
                ->values()
                ->all();

            $result['gender'] = [
                'L' => $rows->where('gender', 'L')->pluck('student_id')->unique()->count(),
                'P' => $rows->where('gender', 'P')->pluck('student_id')->unique()->count(),
            ];
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::distribution — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // KEHADIRAN
    // ─────────────────────────────────────────────────────────────

    /**
     * Tren kehadiran santri 14 hari terakhir + ringkasan hari ini.
     *
     * @return array{series: array<int, array{label: string, hadir: int, total: int, rate: ?float}>, today_rate: ?float, today_present: ?int, today_total: ?int, below_threshold: array<int, array{name: string, rate: float}>}
     */
    public function attendanceTrend(array $schoolIds, int $days = 14): array
    {
        $result = [
            'series' => [], 'today_rate' => null, 'today_present' => null,
            'today_total' => null, 'below_threshold' => [],
        ];

        try {
            $series = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $row = DB::table('admin_presensi_harian')
                    ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                    ->whereDate('tanggal', $date)
                    ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hadir', ['hadir'])
                    ->first();

                $total = (int) ($row->total ?? 0);
                $hadir = (int) ($row->hadir ?? 0);

                $series[] = [
                    'label' => now()->subDays($i)->translatedFormat('d M'),
                    'hadir' => $hadir,
                    'total' => $total,
                    'rate' => $total > 0 ? round(($hadir / $total) * 100, 2) : null,
                ];
            }

            $result['series'] = $series;

            $today = $series[$days - 1] ?? null;
            $result['today_rate'] = $today['rate'] ?? null;
            $result['today_present'] = $today['hadir'] ?? null;
            $result['today_total'] = $today['total'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::attendanceTrend — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // MUTASI
    // ─────────────────────────────────────────────────────────────

    /**
     * Mutasi masuk/keluar per bulan (12 bulan terakhir).
     *
     * @return array{series: array<int, array{label: string, masuk: int, keluar: int}>, total_in: int, total_out: int, net: int, source: string}
     */
    public function mutationTrend(array $schoolIds, int $months = 12): array
    {
        $series = [];
        $totalIn = 0;
        $totalOut = 0;

        try {
            for ($i = $months - 1; $i >= 0; $i--) {
                $start = now()->subMonths($i)->startOfMonth();
                $end = now()->subMonths($i)->endOfMonth();

                $masuk = DB::table('student_mutations_in')
                    ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                    ->where('status', 'approved')
                    ->whereBetween('established_date', [$start->toDateString(), $end->toDateString()])
                    ->count();

                $keluar = DB::table('student_mutations_out')
                    ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                    ->where('status', 'approved')
                    ->whereBetween('established_date', [$start->toDateString(), $end->toDateString()])
                    ->count();

                $totalIn += $masuk;
                $totalOut += $keluar;

                $series[] = [
                    'label' => $start->translatedFormat('M Y'),
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::mutationTrend — '.$e->getMessage());
        }

        return [
            'series' => $series,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net' => $totalIn - $totalOut,
            'source' => 'student_mutations_in, student_mutations_out',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // GTK
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{total: ?int, guru: ?int, tendik: ?int, pending: ?int, contract_ending: ?int, source: string}
     */
    public function gtkStats(array $schoolIds): array
    {
        try {
            $base = DB::table('gtk_employments')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds));

            return [
                'total' => (clone $base)->count(),
                'guru' => (clone $base)->where(fn ($q) => $q->where('jenis_gtk', 'like', '%guru%')->orWhere('jabatan', 'like', '%guru%'))->count(),
                'tendik' => (clone $base)->where(fn ($q) => $q->where('jenis_gtk', 'like', '%tendik%')->orWhere('jenis_gtk', 'like', '%tu%'))->count(),
                'pending' => null,
                'contract_ending' => null,
                'source' => 'gtk_employments',
            ];
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::gtkStats — '.$e->getMessage());

            return ['total' => null, 'guru' => null, 'tendik' => null, 'pending' => null, 'contract_ending' => null, 'source' => 'gtk_employments'];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ROMBEL
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{total: ?int, capacity: ?int, filled: ?int, without_homeroom: ?int, over_capacity: ?int, source: string}
     */
    public function rombelStats(array $schoolIds, ?string $academicYearId, array $filters): array
    {
        try {
            $rows = DB::table('study_groups as sg')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('sg.school_id', $schoolIds))
                ->when($academicYearId, fn ($q) => $q->where('sg.academic_year_id', $academicYearId))
                ->when($filters['grade_level_id'] ?? null, fn ($q, $id) => $q->where('sg.grade_level_id', $id))
                ->where('sg.is_active', 1)
                ->leftJoin('student_class_histories as h', function ($join) {
                    $join->on('h.study_group_id', '=', 'sg.id')->where('h.is_active', 1);
                })
                ->selectRaw('sg.id, sg.name, sg.capacity, sg.homeroom_teacher_id, COUNT(DISTINCT h.student_id) as filled')
                ->groupBy('sg.id', 'sg.name', 'sg.capacity', 'sg.homeroom_teacher_id')
                ->get();

            return [
                'total' => $rows->count(),
                'capacity' => (int) $rows->sum('capacity'),
                'filled' => (int) $rows->sum('filled'),
                'without_homeroom' => $rows->filter(fn ($r) => empty($r->homeroom_teacher_id))->count(),
                'over_capacity' => $rows->filter(fn ($r) => $r->capacity > 0 && $r->filled > $r->capacity)->count(),
                'rows' => $rows->sortByDesc('filled')->values()->all(),
                'source' => 'study_groups, student_class_histories',
            ];
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::rombelStats — '.$e->getMessage());

            return ['total' => null, 'capacity' => null, 'filled' => null, 'without_homeroom' => null, 'over_capacity' => null, 'rows' => [], 'source' => 'study_groups'];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // KESEHATAN AKADEMIK
    // ─────────────────────────────────────────────────────────────

    /**
     * Kelengkapan nilai & keterlaksanaan pembelajaran.
     *
     * @return array{nilai_belum_lengkap: ?int, nilai_total: ?int, jurnal_kosong: ?int, perangkat_kurang: ?int, source: string}
     */
    public function academicHealth(array $schoolIds, ?string $academicYearId): array
    {
        $result = [
            'nilai_belum_lengkap' => null, 'nilai_total' => null,
            'jurnal_kosong' => null, 'perangkat_kurang' => null,
            'source' => 'admin_nilai_sumatif, admin_jurnal_pembelajaran',
        ];

        try {
            $books = DB::table('teacher_admin_books')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->where('is_active', 1);

            $bookIds = (clone $books)->pluck('id')->all();

            if ($bookIds === []) {
                return $result;
            }

            $result['nilai_total'] = DB::table('admin_nilai_sumatif')
                ->whereIn('admin_book_id', $bookIds)
                ->count();

            // Baris nilai yang seluruh komponennya kosong dianggap belum lengkap.
            $result['nilai_belum_lengkap'] = DB::table('admin_nilai_sumatif')
                ->whereIn('admin_book_id', $bookIds)
                ->where(fn ($q) => $q
                    ->whereNull('s1')->whereNull('s2')->whereNull('s3')
                    ->whereNull('sts')->whereNull('sas'))
                ->count();

            $result['jurnal_kosong'] = DB::table('teacher_admin_books')
                ->whereIn('id', $bookIds)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('admin_jurnal_pembelajaran')
                    ->whereColumn('admin_jurnal_pembelajaran.admin_book_id', 'teacher_admin_books.id'))
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::academicHealth — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // KEUANGAN
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{tagihan: ?float, terbayar: ?float, tunggakan: ?float, collection_rate: ?float, source: string}
     */
    public function financeStats(array $schoolIds): array
    {
        try {
            $period = now()->format('Y-m');

            $rows = DB::table('spp_bills')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->where('period', $period)
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(amount), 0) as tagihan, COALESCE(SUM(paid_amount), 0) as terbayar')
                ->first();

            $tagihan = (float) ($rows->tagihan ?? 0);
            $terbayar = (float) ($rows->terbayar ?? 0);

            return [
                'tagihan' => $tagihan,
                'terbayar' => $terbayar,
                'tunggakan' => max(0, $tagihan - $terbayar),
                'bill_count' => (int) ($rows->total ?? 0),
                'collection_rate' => $tagihan > 0 ? round(($terbayar / $tagihan) * 100, 2) : null,
                'source' => 'spp_bills',
            ];
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::financeStats — '.$e->getMessage());

            return ['tagihan' => null, 'terbayar' => null, 'tunggakan' => null, 'collection_rate' => null, 'source' => 'spp_bills'];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ASRAMA
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{santri: ?int, kamar: ?int, izin_aktif: ?int, di_luar: ?int, pelanggaran_bulan: ?int, source: string}
     */
    public function boardingStats(array $schoolIds): array
    {
        $result = ['santri' => null, 'kamar' => null, 'izin_aktif' => null, 'di_luar' => null, 'pelanggaran_bulan' => null, 'source' => 'dormitories, dormitory_permits'];

        try {
            $dormitoryIds = DB::table('dormitories')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->where('is_active', 1)
                ->pluck('id')
                ->all();

            $result['kamar'] = count($dormitoryIds);

            $result['santri'] = DB::table('dormitory_residents')
                ->when($dormitoryIds !== [], fn ($q) => $q->whereIn('dormitory_id', $dormitoryIds))
                ->where('is_active', 1)
                ->distinct()
                ->count('student_id');

            $result['izin_aktif'] = DB::table('dormitory_permits')
                ->whereIn('dormitory_id', $dormitoryIds)
                ->whereIn('status', ['approved', 'out', 'permitted'])
                ->whereNull('actual_return_datetime')
                ->count();

            $result['terlambat'] = DB::table('dormitory_permits')
                ->whereIn('dormitory_id', $dormitoryIds)
                ->whereNull('actual_return_datetime')
                ->whereNotNull('expected_return_datetime')
                ->where('expected_return_datetime', '<', now())
                ->count();

            $result['pelanggaran_bulan'] = DB::table('dormitory_violations')
                ->when($dormitoryIds !== [], fn ($q) => $q->whereIn('dormitory_id', $dormitoryIds))
                ->whereBetween('violation_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::boardingStats — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // TAHFIDZ
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{halaqah: ?int, setoran_hari_ini: ?int, santri_aktif: ?int, tanpa_setoran: ?int, source: string}
     */
    public function tahfidzStats(array $schoolIds): array
    {
        $result = ['halaqah' => null, 'setoran_hari_ini' => null, 'santri_aktif' => null, 'tanpa_setoran' => null, 'source' => 'tahfidz_groups, tahfidz_setorans'];

        try {
            $groupIds = DB::table('tahfidz_groups')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->where('is_active', 1)
                ->pluck('id')
                ->all();

            $result['halaqah'] = count($groupIds);

            $result['santri_aktif'] = DB::table('tahfidz_group_members')
                ->when($groupIds !== [], fn ($q) => $q->whereIn('tahfidz_group_id', $groupIds))
                ->where('is_active', 1)
                ->distinct()
                ->count('student_id');

            $result['setoran_hari_ini'] = DB::table('tahfidz_setorans')
                ->when($groupIds !== [], fn ($q) => $q->whereIn('tahfidz_group_id', $groupIds))
                ->whereDate('setoran_date', today())
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::tahfidzStats — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // UKS
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{kunjungan_hari_ini: ?int, di_uks: ?int, rujukan_bulan: ?int, stok_kritis: ?int, source: string}
     */
    public function healthStats(array $schoolIds): array
    {
        $result = ['kunjungan_hari_ini' => null, 'di_uks' => null, 'rujukan_bulan' => null, 'stok_kritis' => null, 'source' => 'uks_patients, uks_referrals'];

        try {
            $result['kunjungan_hari_ini'] = DB::table('uks_patients')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->whereDate('queue_date', today())
                ->count();

            $result['di_uks'] = DB::table('uks_patients')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->whereNull('discharged_at')
                ->whereNotIn('queue_status', ['selesai', 'batal'])
                ->count();

            $result['rujukan_bulan'] = DB::table('uks_referrals')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->whereBetween('referred_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::healthStats — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // SDM
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{cuti_aktif: ?int, presensi_hari_ini: ?float, aduan_baru: ?int, aduan_selesai: ?int, source: string}
     */
    public function sdmStats(array $schoolIds): array
    {
        $result = ['cuti_aktif' => null, 'presensi_hari_ini' => null, 'aduan_baru' => null, 'aduan_selesai' => null, 'source' => 'cuti_requests, admin_presensi_gtk, complaints'];

        try {
            $result['cuti_aktif'] = DB::table('cuti_requests')
                ->where('status', 'approved')
                ->whereDate('tanggal_mulai', '<=', today())
                ->whereDate('tanggal_selesai', '>=', today())
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::sdmStats — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // SARPRAS
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{tiket_terbuka: ?int, tiket_lewat_sla: ?int, aset_rusak: ?int, source: string}
     */
    public function sarprasStats(array $schoolIds): array
    {
        $result = ['tiket_terbuka' => null, 'tiket_lewat_sla' => null, 'aset_rusak' => null, 'source' => 'sarpras_sla_trackers'];

        try {
            $result['tiket_terbuka'] = DB::table('sarpras_sla_trackers')
                ->whereNull('completed_at')
                ->count();

            $result['tiket_lewat_sla'] = DB::table('sarpras_sla_trackers')
                ->whereNull('completed_at')
                ->whereNotNull('deadline_at')
                ->where('deadline_at', '<', now())
                ->count();

            $result['eskalasi'] = DB::table('sarpras_sla_trackers')
                ->where('escalation_level', '>', 0)
                ->whereNull('completed_at')
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::sarprasStats — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // GURU & WALI KELAS
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{books: ?int, students: ?int, nilai_kosong: ?int, jurnal_kosong: ?int, source: string}
     */
    public function teacherStats(array $schoolIds, ?string $academicYearId, ?string $teacherId = null): array
    {
        $result = ['books' => null, 'students' => null, 'nilai_kosong' => null, 'jurnal_kosong' => null, 'source' => 'teacher_admin_books, admin_nilai_sumatif'];

        try {
            $books = DB::table('teacher_admin_books')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->where('is_active', 1)
                ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId));

            $bookIds = (clone $books)->pluck('id')->all();

            $result['books'] = count($bookIds);

            if ($bookIds === []) {
                return $result;
            }

            $result['students'] = DB::table('admin_nilai_sumatif')
                ->whereIn('admin_book_id', $bookIds)
                ->distinct()
                ->count('student_id');

            $result['nilai_kosong'] = DB::table('admin_nilai_sumatif')
                ->whereIn('admin_book_id', $bookIds)
                ->where(fn ($q) => $q->whereNull('s1')->whereNull('s2')->whereNull('s3')->whereNull('sts')->whereNull('sas'))
                ->count();

            $result['jurnal_kosong'] = DB::table('teacher_admin_books')
                ->whereIn('id', $bookIds)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('admin_jurnal_pembelajaran')
                    ->whereColumn('admin_jurnal_pembelajaran.admin_book_id', 'teacher_admin_books.id'))
                ->count();
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::teacherStats — '.$e->getMessage());
        }

        return $result;
    }

    /**
     * @return array{classes: ?int, students: ?int, attendance_rate: ?float, violations: ?int, source: string}
     */
    public function homeroomStats(array $schoolIds, ?string $academicYearId, array $filters, ?string $teacherId = null): array
    {
        $result = ['classes' => null, 'students' => null, 'attendance_rate' => null, 'violations' => null, 'source' => 'study_groups, student_class_histories'];

        try {
            $groups = DB::table('study_groups')
                ->when($schoolIds !== [], fn ($q) => $q->whereIn('school_id', $schoolIds))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->when($teacherId, fn ($q) => $q->where('homeroom_teacher_id', $teacherId))
                ->where('is_active', 1)
                ->pluck('id')
                ->all();

            $result['classes'] = count($groups);

            if ($groups === []) {
                return $result;
            }

            $result['students'] = DB::table('student_class_histories')
                ->whereIn('study_group_id', $groups)
                ->where('is_active', 1)
                ->distinct()
                ->count('student_id');
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::homeroomStats — '.$e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // SISTEM
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{pengguna: ?int, aktif: ?int, tanpa_role: ?int, failed_job: ?int, antrean: ?int, aktivitas_hari_ini: ?int, source: string}
     */
    public function systemStats(): array
    {
        try {
            return [
                'pengguna' => DB::table('users')->whereNull('deleted_at')->count(),
                'aktif' => DB::table('users')->where('is_active', 1)->whereNull('deleted_at')->count(),
                'tanpa_role' => DB::table('users')->whereNull('deleted_at')
                    ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('model_has_roles')->whereColumn('model_has_roles.model_id', 'users.id'))
                    ->count(),
                'failed_job' => DB::table('failed_jobs')->count(),
                'antrean' => DB::table('jobs')->count(),
                'aktivitas_hari_ini' => DB::table('activity_log')->whereDate('created_at', today())->count(),
                'source' => 'users, jobs, failed_jobs, activity_log',
            ];
        } catch (\Throwable $e) {
            Log::warning('DashboardAnalyticsService::systemStats — '.$e->getMessage());

            return ['pengguna' => null, 'aktif' => null, 'tanpa_role' => null, 'failed_job' => null, 'antrean' => null, 'aktivitas_hari_ini' => null, 'source' => 'users'];
        }
    }
}
