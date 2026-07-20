<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use App\Services\RepairRequestService;
use App\Services\Sarpras\SarprasNotificationService;
use App\Services\SarprasCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PIC verification & approval controller — web surface for assigning a verification
 * result to a RepairRequest. Mirrors the API surface in Api\Sarpras\RepairRequestController.
 */
class SarprasPicApprovalController extends Controller
{
    public function __construct(
        protected RepairRequestService $repairService,
        protected SarprasNotificationService $notifier,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function index()
    {
        $pending = RepairRequest::with(['asset', 'reportedBy'])
            ->whereIn('status', [
                RepairRequest::STATUS_VERIFICATION_PENDING,
                RepairRequest::STATUS_APPROVAL_PENDING,
            ])
            ->latest()
            ->paginate(20);

        return view('sarpras.pic.index', compact('pending'));
    }

    public function show(string $id)
    {
        $repair = RepairRequest::with(['asset', 'reportedBy', 'workOrders'])->findOrFail($id);

        return view('sarpras.pic.show', compact('repair'));
    }

    public function verify(Request $request, string $id): RedirectResponse
    {
        $repair = RepairRequest::findOrFail($id);

        $data = $request->validate([
            'recommendation' => 'required|in:approved,rejected',
            'verification_notes' => 'nullable|string|max:1000',
            'verified_at' => 'nullable|date',
        ]);

        if ($data['recommendation'] === RepairRequest::RECOMMENDATION_APPROVED) {
            $this->repairService->startVerification($repair);
            $this->repairService->submitVerification($repair, Auth::id(), [
                'recommendation' => RepairRequest::RECOMMENDATION_APPROVED,
                'verification_notes' => $data['verification_notes'] ?? null,
            ]);
            $this->repairService->requestApproval($repair);
            $this->repairService->approveForRepair($repair, Auth::id());
        } else {
            $this->repairService->rejectVerification(
                $repair,
                Auth::id(),
                $data['verification_notes'] ?? 'Ditolak oleh PIC.'
            );
        }

        $this->notifier->dispatchRepairStatusChange($repair->fresh());

        $this->cacheInvalidator->invalidateAll();

        return redirect()
            ->route('sarpras.pic.index')
            ->with('success', "Verifikasi untuk {$repair->request_number} berhasil disimpan.");
    }
}
