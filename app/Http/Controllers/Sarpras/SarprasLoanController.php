<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Requests\Sarpras\LoanStoreRequest;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\School;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\AssetStatusTransitionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SarprasLoanController extends SarprasBaseController
{
    public function __construct(
        public AssetEventLogger $eventLogger,
        public AssetStatusTransitionService $transition,
    ) {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    public function index(Request $request)
    {
        $query = AssetLoan::with(['asset', 'borrower', 'approver']);

        if (! $this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('asset', fn ($q) => $q->where('asset_name', 'like', "%{$s}%"));
        }

        $loans = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.peminjaman.index', compact('loans', 'schools'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $assets = Asset::where('is_active', true)
            ->where('is_bookable', true)
            ->where('status', 'tersedia')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        return view('sarpras.peminjaman.create', compact('assets'));
    }

    public function store(LoanStoreRequest $request)
    {
        $validated = $request->validated();

        $asset = Asset::findOrFail($validated['asset_id']);

        $validated['borrower_id'] = auth()->id();
        $validated['work_unit_id'] = $asset->work_unit_id;
        $validated['school_id'] = $asset->school_id;
        $validated['condition_on_loan'] = $asset->condition;
        $validated['status'] = 'pending';

        AssetLoan::create($validated);

        $this->bumpDashboardCache();

        return redirect()->route('sarpras.peminjaman.index')
            ->with('success', 'Permintaan peminjaman berhasil diajukan.');
    }

    public function show(Request $request, string $id)
    {
        $loan = AssetLoan::with(['asset', 'borrower', 'approver', 'returnedToUser'])->findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        return view('sarpras.peminjaman.show', compact('loan'));
    }

    public function approve(Request $request, string $id)
    {
        $loan = AssetLoan::findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        if ($loan->status !== 'pending') {
            return back()->with('error', 'Peminjaman ini sudah diproses.');
        }

        $loan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Peminjaman berhasil disetujui.');
    }

    public function reject(Request $request, string $id)
    {
        $loan = AssetLoan::findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        if ($loan->status !== 'pending') {
            return back()->with('error', 'Peminjaman ini sudah diproses.');
        }

        $loan->update([
            'status' => 'dibatalkan',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Peminjaman ditolak.');
    }

    public function handover(Request $request, string $id)
    {
        $loan = AssetLoan::findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        if ($loan->status !== 'approved') {
            return back()->with('error', 'Peminjaman belum disetujui.');
        }

        $loan->update(['status' => 'dipinjam']);
        $this->transition->transition($loan->asset, 'borrowed', auth()->id(), 'Handed over for loan');
        $this->bumpDashboardCache();

        try {
            $this->eventLogger->logLoanCreated($loan->asset, $loan->borrower->name ?? 'unknown', auth()->id());
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Aset berhasil diserahkan ke peminjam.');
    }

    public function return(Request $request, string $id)
    {
        $loan = AssetLoan::findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        if ($loan->status !== 'dipinjam') {
            return back()->with('error', 'Aset belum dipinjam.');
        }

        $validated = $request->validated();

        $loan->update([
            'status' => 'dikembalikan',
            'actual_return_date' => Carbon::today(),
            'actual_return_time' => Carbon::now()->format('H:i:s'),
            'returned_to' => auth()->id(),
            'condition_on_return' => $validated['condition_on_return'],
            'damage_notes' => $validated['damage_notes'],
        ]);

        $loan->asset->update([
            'condition' => $validated['condition_on_return'],
        ]);
        $this->transition->transition($loan->asset, 'active', auth()->id(), 'Loan returned');
        $this->bumpDashboardCache();

        try {
            $this->eventLogger->logLoanReturned($loan->asset, $loan->borrower->name ?? 'unknown', auth()->id());
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('sarpras.peminjaman.index')
            ->with('success', 'Aset berhasil dikembalikan.');
    }

    public function destroy(Request $request, string $id)
    {
        $loan = AssetLoan::findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        if (in_array($loan->status, ['dipinjam'])) {
            return back()->with('error', 'Peminjaman yang sedang berlangsung tidak bisa dihapus.');
        }

        $loan->delete();
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.peminjaman.index')
            ->with('success', 'Data peminjaman berhasil dihapus.');
    }
}
