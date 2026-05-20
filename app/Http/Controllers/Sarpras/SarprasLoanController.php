<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetLoan;
use App\Models\Asset;
use App\Models\School;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SarprasLoanController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    public function index(Request $request)
    {
        $query = AssetLoan::with(['asset', 'borrower', 'approver']);

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('asset', fn($q) => $q->where('asset_name', 'like', "%{$s}%"));
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
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        return view('sarpras.peminjaman.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id'              => 'required|exists:assets,id',
            'purpose'               => 'required|string',
            'loan_date'             => 'required|date',
            'loan_time'             => 'nullable',
            'expected_return_date'  => 'required|date|after_or_equal:loan_date',
            'notes'                 => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        $validated['borrower_id'] = auth()->id();
        $validated['work_unit_id'] = $asset->work_unit_id;
        $validated['school_id'] = $asset->school_id;
        $validated['condition_on_loan'] = $asset->condition;
        $validated['status'] = 'pending';

        AssetLoan::create($validated);

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
        $loan->asset->update(['status' => 'dipinjam']);

        return back()->with('success', 'Aset berhasil diserahkan ke peminjam.');
    }

    public function return(Request $request, string $id)
    {
        $loan = AssetLoan::findOrFail($id);
        $this->authorizeLoanAccess($loan, $request);

        if ($loan->status !== 'dipinjam') {
            return back()->with('error', 'Aset belum dipinjam.');
        }

        $validated = $request->validate([
            'condition_on_return' => 'required|in:' . implode(',', AssetLoan::CONDITION_OPTIONS),
            'damage_notes'       => 'nullable|string',
        ]);

        $loan->update([
            'status'               => 'dikembalikan',
            'actual_return_date'   => Carbon::today(),
            'actual_return_time'   => Carbon::now()->format('H:i:s'),
            'returned_to'          => auth()->id(),
            'condition_on_return'  => $validated['condition_on_return'],
            'damage_notes'         => $validated['damage_notes'],
        ]);

        $loan->asset->update([
            'status'    => 'tersedia',
            'condition' => $validated['condition_on_return'],
        ]);

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

        return redirect()->route('sarpras.peminjaman.index')
            ->with('success', 'Data peminjaman berhasil dihapus.');
    }
}
