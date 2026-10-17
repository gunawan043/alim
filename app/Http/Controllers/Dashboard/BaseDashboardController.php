<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\GtkEmployment;
use App\Models\Student;
use App\Models\StudyGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseDashboardController extends Controller
{
    /**
     * Resolve school_id user login.
     * Prioritas: gtk_employment → session → first school.
     */
    protected function resolveSchoolId(): ?string
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $gtk = GtkEmployment::where('user_id', $user->id)->first();
        if ($gtk && $gtk->school_id) {
            return $gtk->school_id;
        }

        if ($sid = session('school_id')) {
            return $sid;
        }

        return Cache::remember('default_school_id', 3600, function () {
            return DB::table('schools')->value('id');
        });
    }

    protected function resolveAcademicYearId(): ?string
    {
        return Cache::remember('academic_year_active_id', 3600, function () {
            return AcademicYear::where('is_active', true)->value('id');
        });
    }

    protected function resolveGtkEmployment(): ?GtkEmployment
    {
        return GtkEmployment::where('user_id', Auth::id())->first();
    }

    protected function resolveHomeroomStudyGroup(): ?StudyGroup
    {
        $gtk = $this->resolveGtkEmployment();
        if (! $gtk) {
            return null;
        }

        return StudyGroup::where('homeroom_teacher_id', $gtk->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Statistik santri aktif.
     */
    protected function statsSantri(): array
    {
        $schoolId = $this->resolveSchoolId();

        $base = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active');

        $total = (clone $base)->count();

        $bulanIni = (clone $base)
            ->whereMonth('entry_date', now()->month)
            ->whereYear('entry_date', now()->year)
            ->count();

        $bulanLalu = (clone $base)
            ->whereMonth('entry_date', now()->subMonth()->month)
            ->whereYear('entry_date', now()->subMonth()->year)
            ->count();

        $gender = (clone $base)
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->toArray();

        return [
            'total' => $total,
            'bulan_ini' => $bulanIni,
            'bulan_lalu' => $bulanLalu,
            'trend' => $bulanLalu > 0
                ? round((($bulanIni - $bulanLalu) / $bulanLalu) * 100, 1)
                : 0,
            'gender' => [
                'L' => $gender['L'] ?? 0,
                'P' => $gender['P'] ?? 0,
            ],
        ];
    }

    /**
     * Statistik GTK.
     * `jenis_gtk` bisa berupa ID (jenis_gtk_id) atau string. Pakai LIKE.
     */
    protected function statsGtk(): array
    {
        $schoolId = $this->resolveSchoolId();

        $query = GtkEmployment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        return [
            'total' => (clone $query)->count(),
            'guru' => (clone $query)
                ->where(function ($q) {
                    $q->where('jenis_gtk', 'like', '%guru%')
                      ->orWhere('jabatan', 'like', '%guru%');
                })
                ->count(),
            'tendik' => (clone $query)
                ->where(function ($q) {
                    $q->where('jenis_gtk', 'like', '%tendik%')
                      ->orWhere('jenis_gtk', 'like', '%tu%')
                      ->orWhere('jabatan', 'like', '%tata usaha%');
                })
                ->count(),
        ];
    }

    /**
     * Statistik rombel.
     */
    protected function statsRombel(): array
    {
        $schoolId = $this->resolveSchoolId();
        $academicYearId = $this->resolveAcademicYearId();

        $query = StudyGroup::query()->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        return [
            'total' => $query->count(),
            'total_kapasitas' => (clone $query)->sum('capacity'),
        ];
    }

    /**
     * Daftar rombel + kapasitas (pengganti top-rombel).
     */
    protected function listRombel(int $limit = 10): array
    {
        $schoolId = $this->resolveSchoolId();
        $academicYearId = $this->resolveAcademicYearId();

        return StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('is_active', true)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'capacity', 'room', 'shift'])
            ->map(fn ($sg) => [
                'name' => $sg->name,
                'capacity' => $sg->capacity,
                'room' => $sg->room,
                'shift' => $sg->shift,
            ])
            ->toArray();
    }

    /**
     * Audit log terbaru.
     */
    protected function recentAuditLogs(int $limit = 10): array
    {
        try {
            return AuditLog::query()
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'event' => $log->event ?? '-',
                    'description' => $log->description ?? '-',
                    'user' => optional($log->user)->name ?? 'System',
                    'created_at' => optional($log->created_at)->diffForHumans() ?? '-',
                ])
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('AuditLog query failed: ' . $e->getMessage());
            return [];
        }
    }
}