<?php

declare(strict_types=1);

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Uks\UksPatient;
use App\Services\Uks\UksClinicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CareLocationController — papan pendataan lokasi perawatan santri.
 *
 * Menjawab kebutuhan: siapa yang dirawat di asrama, di ruang UKS/ranjang,
 * tetap ke sekolah, pulang ke rumah, dirujuk ke faskes, atau lokasi lainnya.
 */
class CareLocationController extends Controller
{
    public function __construct(private readonly UksClinicService $clinic) {}

    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $genderFilter = $this->genderFilter();

        $currentStatuses = [
            UksPatient::STATUS_WAITING,
            UksPatient::STATUS_TREATED,
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
        ];

        $base = UksPatient::query()
            ->with([
                'student.activeDormitoryResident.dormitory',
                'currentBedAssignment.bed',
                'latestEncounter',
                'admittedBy',
                'locationUpdatedBy',
            ])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($genderFilter, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('gender', $genderFilter)))
            ->where(function ($query) use ($currentStatuses) {
                $query->whereIn('status', $currentStatuses)
                    ->orWhereDate('discharged_at', today())
                    ->orWhereDate('location_updated_at', today());
            });

        $stats = [];
        foreach (array_keys(UksPatient::$careLocationLabels) as $location) {
            $stats[$location] = (clone $base)->where('care_location', $location)->count();
        }

        $query = clone $base;

        $query->when($request->filled('care_location'), fn ($q) => $q->where('care_location', $request->care_location))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('medical_record_no', 'like', "%{$search}%")
                        ->orWhere('queue_number', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->orWhere('nisn', 'like', "%{$search}%"));
                });
            });

        $patients = $query
            ->orderByRaw("CASE care_location
                WHEN 'uks' THEN 1
                WHEN 'asrama' THEN 2
                WHEN 'sekolah' THEN 3
                WHEN 'faskes' THEN 4
                WHEN 'rumah' THEN 5
                ELSE 6 END")
            ->orderByDesc('location_updated_at')
            ->orderByDesc('admitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('uks.care-locations.index', [
            'patients' => $patients,
            'stats' => $stats,
            'genderFilter' => $genderFilter,
        ]);
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'care_location' => 'required|in:'.implode(',', array_keys(UksPatient::$careLocationLabels)),
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->clinic->updateCareLocation(
            $patient,
            $validated['care_location'],
            $validated['notes'] ?? null,
            Auth::id()
        );

        return back()->with(
            'success',
            'Lokasi perawatan '.($patient->student?->name ?? 'pasien').' diubah menjadi '.
            (UksPatient::$careLocationLabels[$validated['care_location']] ?? $validated['care_location']).'.'
        );
    }

    private function genderFilter(): ?string
    {
        $workUnit = auth()->user()?->primaryWorkUnit;
        $name = $workUnit?->name ?? '';

        if (str_contains($name, 'Putra')) {
            return 'L';
        }

        if (str_contains($name, 'Putri')) {
            return 'P';
        }

        return null;
    }
}
