<?php

declare(strict_types=1);

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\StudentMedicineInventory;
use App\Models\Uks\UksPrescription;
use App\Services\Uks\UksClinicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PharmacyController — antrean resep dan penyerahan obat UKS.
 */
class PharmacyController extends Controller
{
    public function __construct(private readonly UksClinicService $clinic) {}

    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $base = UksPrescription::query()
            ->with(['patient.student', 'items.inventory', 'prescribedBy', 'dispensedBy'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId));

        $stats = [
            'pending' => (clone $base)->where('status', UksPrescription::STATUS_NEW)->count(),
            'prepared' => (clone $base)->where('status', UksPrescription::STATUS_PREPARED)->count(),
            'dispensed_today' => (clone $base)->where('status', UksPrescription::STATUS_DISPENSED)
                ->whereDate('dispensed_at', today())->count(),
            'low_stock' => StudentMedicineInventory::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->lowStock()->count(),
        ];

        $query = clone $base;
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('prescription_no', 'like', "%{$search}%")
                        ->orWhereHas('patient.student', fn ($student) => $student->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items', fn ($item) => $item->where('medicine_name', 'like', "%{$search}%"));
                });
            });

        $prescriptions = $query->orderByRaw("CASE status WHEN 'baru' THEN 1 WHEN 'disiapkan' THEN 2 WHEN 'diserahkan' THEN 3 ELSE 4 END")
            ->orderBy('prescribed_at')
            ->paginate(20)
            ->withQueryString();

        $lowStock = StudentMedicineInventory::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->lowStock()
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $expiring = StudentMedicineInventory::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(90))
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        return view('uks.pharmacy.index', [
            'prescriptions' => $prescriptions,
            'stats' => $stats,
            'lowStock' => $lowStock,
            'expiring' => $expiring,
        ]);
    }

    public function dispense(Request $request, string $userId, string $uuid)
    {
        $prescription = UksPrescription::with(['items.inventory', 'patient'])->findOrFail($uuid);

        $validated = $request->validate([
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|numeric|min:0|max:10000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->clinic->dispensePrescription(
            $prescription,
            $validated['quantities'] ?? [],
            Auth::id()
        );

        return back()->with('success', 'Obat berhasil diserahkan dan stok telah diperbarui.');
    }

    public function cancel(Request $request, string $userId, string $uuid)
    {
        $prescription = UksPrescription::findOrFail($uuid);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->clinic->cancelPrescription($prescription, $validated['reason'] ?? null, Auth::id());

        return back()->with('success', 'Resep dibatalkan.');
    }
}
