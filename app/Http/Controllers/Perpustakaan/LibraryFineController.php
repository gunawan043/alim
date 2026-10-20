<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LibraryLoan;
use App\Models\LibraryMember;
use App\Models\LibrarySetting;
use App\Models\LibraryUnit;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;

class LibraryFineController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $yearId = AcademicYear::where('is_active', true)->value('id');
        $setting = LibrarySetting::forUnitYear($unit->id, $yearId);
        $tab = $request->input('tab', 'fines');
        $search = trim((string) $request->input('search', ''));

        $loans = LibraryLoan::with(['member.student:id,name,nis', 'member.user:id,name', 'items.item.title:id,title'])
            ->where('library_unit_id', $unit->id)
            ->where(fn ($q) => $q->where('fine_amount', '>', 0)->orWhere(fn ($q2) => $q2->where('status', LibraryLoan::STATUS_ACTIVE)->whereDate('due_date', '<', today())))
            ->when($request->input('status') === 'unpaid', fn ($q) => $q->whereNull('fine_paid_at')->whereNull('fine_waived_at'))
            ->when($request->input('status') === 'paid', fn ($q) => $q->whereNotNull('fine_paid_at'))
            ->when($request->input('status') === 'waived', fn ($q) => $q->whereNotNull('fine_waived_at'))
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('loan_number', 'like', '%'.$search.'%')->orWhereHas('member.student', fn ($st) => $st->where('name', 'like', '%'.$search.'%'))))
            ->latest()->limit(300)->get();
        $loans->each(function (LibraryLoan $loan) use ($setting) {
            $days = $loan->status === LibraryLoan::STATUS_ACTIVE ? max(0, $loan->due_date?->diffInDays(today(), false) ?? 0) : 0;
            $estimated = $setting->allow_fine ? $days * (float) $setting->fine_per_day : 0;
            if ($setting->max_fine !== null) {
                $estimated = min($estimated, (float) $setting->max_fine);
            }
            $loan->setAttribute('late_days', $days);
            $loan->setAttribute('estimated_fine', (float) ($loan->fine_amount > 0 ? $loan->fine_amount : $estimated));
        });

        $members = collect();
        if ($tab === 'free') {
            $members = LibraryMember::with(['student:id,name,nis', 'user:id,name'])
                ->withCount(['loans as active_loans_count' => fn ($q) => $q->where('status', LibraryLoan::STATUS_ACTIVE)])
                ->where('library_unit_id', $unit->id)->where('status', LibraryMember::STATUS_ACTIVE)
                ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('member_number', 'like', '%'.$search.'%')->orWhereHas('student', fn ($st) => $st->where('name', 'like', '%'.$search.'%'))))
                ->orderBy('member_number')->limit(300)->get();
            $unpaid = LibraryLoan::where('library_unit_id', $unit->id)
                ->where('fine_amount', '>', 0)->whereNull('fine_paid_at')->whereNull('fine_waived_at')
                ->get()->groupBy('library_member_id');
            $members->each(fn (LibraryMember $member) => $member->setAttribute('unpaid_fine', (float) ($unpaid->get($member->id)?->sum('fine_amount') ?? 0)));
        }

        $stats = [
            'outstanding' => $loans->whereNull('fine_paid_at')->whereNull('fine_waived_at')->sum('estimated_fine'),
            'paid' => $loans->whereNotNull('fine_paid_at')->sum('fine_amount'),
            'waived' => $loans->whereNotNull('fine_waived_at')->sum('fine_amount'),
            'overdue' => $loans->where('status', LibraryLoan::STATUS_ACTIVE)->where('late_days', '>', 0)->count(),
        ];

        return view('perpustakaan.fines.index', compact('userId', 'units', 'unit', 'setting', 'tab', 'search', 'loans', 'members', 'stats'));
    }

    public function pay(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $loan = LibraryLoan::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $loan->unit), 403);
        $validated = $request->validate(['fine_notes' => 'nullable|string|max:2000']);
        abort_if($loan->fine_amount <= 0, 422, 'Tidak ada denda pada transaksi ini.');
        $loan->update(['fine_paid_at' => now(), 'fine_notes' => $validated['fine_notes'] ?? $loan->fine_notes]);

        return back()->with('success', 'Pembayaran denda dicatat.');
    }

    public function waive(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $loan = LibraryLoan::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $loan->unit), 403);
        $validated = $request->validate(['fine_notes' => 'required|string|max:2000']);
        $loan->update(['fine_waived_at' => now(), 'fine_notes' => $validated['fine_notes']]);

        return back()->with('success', 'Denda dibebaskan dengan catatan.');
    }
}
