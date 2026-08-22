<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryViolation;
use App\Models\GtkProfile;
use App\Models\Student;
use App\Models\Uks\UksPatient;
use App\Models\UksBed;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * UKS Dashboard — comprehensive dashboard for Kepala UKS / Admin Kesehatan.
 */
class UksDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $schoolId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();

        // Gender filter: UKS Putra = 'L', UKS Putri = 'P'
        $genderFilter = null;
        $primaryWorkUnit = $currentUser->primaryWorkUnit;
        if ($primaryWorkUnit) {
            if (str_contains($primaryWorkUnit->name ?? '', 'Putra')) {
                $genderFilter = 'L';
            } elseif (str_contains($primaryWorkUnit->name ?? '', 'Putri')) {
                $genderFilter = 'P';
            }
        }
        // Fallback: if role is UKS without work unit, allow all (no filter)

        // ── Card 1: Total Santri ────────────────────────────────
        $totalSantriQuery = Student::whereHas('activeDormitoryResident')
            ->where('status', 'active');
        if ($schoolId) {
            $totalSantriQuery->where('school_id', $schoolId);
        }
        if ($genderFilter) {
            $totalSantriQuery->where('gender', $genderFilter);
        }
        $totalSantri = $totalSantriQuery->count();

        // ── Card 2: Aktif Sekarang (menunggu + sedang + observasi + rawat) ─
        $activePatients = UksPatient::active()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($genderFilter, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('gender', $genderFilter)))
            ->count();

        // ── Per-status counts ────────────────────────────────────
        $patientStatusCounts = [
            'menunggu' => UksPatient::where('status', UksPatient::STATUS_WAITING)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'sedang_ditangani' => UksPatient::where('status', UksPatient::STATUS_TREATED)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'observasi' => UksPatient::where('status', UksPatient::STATUS_OBSERVATION)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'rawat_uks' => UksPatient::where('status', UksPatient::STATUS_INPATIENT)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'istirahat_di_uks' => UksPatient::where('status', UksPatient::STATUS_RESTING_UKS)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            UksPatient::STATUS_RETURN_DORM => UksPatient::where('status', UksPatient::STATUS_RETURN_DORM)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            UksPatient::STATUS_RETURN_SCHOOL => UksPatient::where('status', UksPatient::STATUS_RETURN_SCHOOL)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            UksPatient::STATUS_PICKED_UP => UksPatient::where('status', UksPatient::STATUS_PICKED_UP)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'pulang' => UksPatient::where('status', UksPatient::STATUS_LEAVING)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'dirujuk' => UksPatient::whereIn('status', [UksPatient::STATUS_REFERRAL_CLINIC, UksPatient::STATUS_REFERRAL_HOSPITAL])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'selesai' => UksPatient::where('status', UksPatient::STATUS_COMPLETED)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
        ];

        // Today's stats
        $patientsToday = UksPatient::today()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($genderFilter, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('gender', $genderFilter)));
        $totalToday = (clone $patientsToday)->count();
        $completedToday = (clone $patientsToday)->where('status', UksPatient::STATUS_COMPLETED)->count();
        $referralsMonth = UksPatient::whereMonth('admitted_at', $today->month)
            ->whereYear('admitted_at', $today->year)
            ->where('referred_to_faskes', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->count();

        // ── Bed Occupancy ────────────────────────────────────────
        $bedStats = $this->getBedStats($schoolId, $genderFilter);

        // ── Recent Active Patients ───────────────────────────────
        $recentPatients = UksPatient::with(['student.dormitory'])
            ->active()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($genderFilter, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('gender', $genderFilter)))
            ->orderByDesc('admitted_at')
            ->take(5)
            ->get();

        // ── GTK with Blood Type Summary ──────────────────────────
        $bloodTypeSummary = [];
        $gtkQuery = GtkProfile::query();
        if ($schoolId) {
            $gtkQuery->whereHas('user.employments', fn ($e) => $e->where('school_id', $schoolId));
        }
        if ($genderFilter) {
            $gtkQuery->where('jenis_kelamin', $genderFilter);
        }
        foreach (['A', 'B', 'AB', 'O'] as $bt) {
            $bloodTypeSummary[$bt] = (clone $gtkQuery)->where('golongan_darah', $bt)->count();
        }

        return view('uks.dashboard.index', [
            'today' => $today,
            'totalSantri' => $totalSantri,
            'activePatients' => $activePatients,
            'healthViolations' => DormitoryViolation::where(function ($q) {
                $q->where('description', 'like', '%sakit%')
                    ->orWhere('description', 'like', '%kes%')
                    ->orWhere('description', 'like', '%medis%');
            })->orWhereRaw('LOWER(description) LIKE ?', ['%medicine%'])
                ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('school_id', $schoolId)))
                ->count(),
            'uksGtkCount' => User::whereHas('gtkProfile', fn ($q) => $q->whereNotNull('golongan_darah'))
                ->when($schoolId, fn ($q) => $q->whereHas('employments', fn ($e) => $e->where('school_id', $schoolId)))
                ->when($genderFilter, fn ($q) => $q->whereHas('gtkProfile', fn ($q) => $q->where('jenis_kelamin', $genderFilter)))
                ->count(),
            'monthReferrals' => $referralsMonth,
            'recentPatients' => $recentPatients,
            'bloodTypeSummary' => $bloodTypeSummary,
            'genderFilter' => $genderFilter,
            // New stats
            'patientStatusCounts' => $patientStatusCounts,
            'totalToday' => $totalToday,
            'completedToday' => $completedToday,
            'bedStats' => $bedStats,
        ]);
    }

    /**
     * Gather bed occupancy statistics grouped by dormitory/room.
     */
    private function getBedStats(?string $schoolId, ?string $genderFilter): array
    {
        $query = UksBed::with(['currentAssignment.patient.student']);

        if ($genderFilter) {
            $query->byGender($genderFilter);
        }

        if ($schoolId) {
            $query->whereHas('dormitory', fn ($d) => $d->where('school_id', $schoolId));
        }

        $allBeds = $query->get();

        $totalBeds = $allBeds->count();
        $occupiedBeds = $allBeds->where('is_occupied', true)->count();
        $availableBeds = $totalBeds - $occupiedBeds;
        $underRepair = $allBeds->where('status', 'perbaikan')->count();

        $groupedByDorm = $allBeds->groupBy(function ($bed) {
            return $bed->dormitory?->name ?? 'Tanpa Asrama';
        });

        return [
            'total' => $totalBeds,
            'occupied' => $occupiedBeds,
            'available' => $availableBeds,
            'under_repair' => $underRepair,
            'grouped_by_dorm' => $groupedByDorm,
        ];
    }
}
