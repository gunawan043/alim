<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\LibraryLoan;
use App\Models\LibraryLoanItem;
use App\Models\LibraryMember;
use App\Models\LibraryMemberCard;
use App\Models\LibrarySetting;
use App\Models\LibraryUnit;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryLoanController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $members = LibraryMember::with(['student:id,name,nis', 'user:id,name'])->where('library_unit_id', $unit->id)->where('status', LibraryMember::STATUS_ACTIVE)->orderBy('member_number')->get();
        $items = LibraryItem::with('title:id,title')->where('library_unit_id', $unit->id)->where('status', LibraryItem::STATUS_AVAILABLE)->orderBy('inventory_code')->get();
        $loans = LibraryLoan::with(['member.student:id,name,nis', 'member.user:id,name', 'items.item.title:id,title'])
            ->where('library_unit_id', $unit->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('loan_number', 'like', '%'.$request->search.'%')->orWhereHas('member.student', fn ($s) => $s->where('name', 'like', '%'.$request->search.'%')))
            ->latest()->limit(200)->get();
        $stats = [
            'active' => LibraryLoan::where('library_unit_id', $unit->id)->where('status', LibraryLoan::STATUS_ACTIVE)->count(),
            'overdue' => LibraryLoan::where('library_unit_id', $unit->id)->where('status', LibraryLoan::STATUS_ACTIVE)->whereDate('due_date', '<', today())->count(),
            'returned' => LibraryLoan::where('library_unit_id', $unit->id)->where('status', LibraryLoan::STATUS_RETURNED)->count(),
        ];

        return view('perpustakaan.loans.index', compact('userId', 'units', 'unit', 'members', 'items', 'loans', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'library_member_id' => 'required|exists:library_members,id',
            'card_number' => 'required|string|max:50',
            'physical_card_verified' => 'required|accepted',
            'loan_date' => 'required|date',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:library_items,id',
            'notes' => 'nullable|string|max:2000',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $member = LibraryMember::where('library_unit_id', $unit->id)->where('status', LibraryMember::STATUS_ACTIVE)->findOrFail($validated['library_member_id']);
        $card = LibraryMemberCard::where('library_member_id', $member->id)
            ->where('card_number', $validated['card_number'])
            ->where('status', LibraryMemberCard::STATUS_ACTIVE)->first();
        abort_unless($card, 422, 'Kartu anggota tidak valid atau tidak aktif. Peminjaman harus memakai kartu fisik yang terdaftar.');
        $setting = LibrarySetting::forUnitYear($unit->id, \App\Models\AcademicYear::where('is_active', true)->value('id'));
        $itemIds = collect($validated['item_ids'])->unique()->values();
        abort_if($itemIds->count() > (int) $setting->max_active_loans, 422, 'Jumlah buku melebihi batas pinjaman.');

        DB::transaction(function () use ($validated, $unit, $member, $setting, $itemIds, $request) {
            $activeCount = LibraryLoanItem::whereHas('loan', fn ($q) => $q->where('library_unit_id', $unit->id)->where('library_member_id', $member->id)->where('status', LibraryLoan::STATUS_ACTIVE))->count();
            abort_if($activeCount + $itemIds->count() > (int) $setting->max_active_loans, 422, 'Batas pinjaman anggota terlampaui.');

            $items = LibraryItem::where('library_unit_id', $unit->id)->whereIn('id', $itemIds)->lockForUpdate()->get();
            abort_if($items->count() !== $itemIds->count(), 422, 'Salah satu eksemplar tidak ditemukan pada unit ini.');
            abort_if($items->contains(fn ($item) => $item->status !== LibraryItem::STATUS_AVAILABLE), 422, 'Salah satu eksemplar sedang tidak tersedia.');

            $loanDate = \Carbon\Carbon::parse($validated['loan_date']);
            $loan = LibraryLoan::create([
                'library_unit_id' => $unit->id,
                'library_member_id' => $member->id,
                'loan_number' => 'LN-'.preg_replace('/[^A-Za-z0-9]/', '', $unit->code).'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(3)),
                'loan_date' => $loanDate->toDateString(),
                'due_date' => $loanDate->copy()->addDays((int) $setting->loan_days)->toDateString(),
                'status' => LibraryLoan::STATUS_ACTIVE,
                'physical_card_verified' => true,
                'card_verified_at' => now(),
                'processed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);
            foreach ($items as $item) {
                LibraryLoanItem::create(['library_loan_id' => $loan->id, 'library_item_id' => $item->id, 'status' => 'loaned']);
                $item->update(['status' => LibraryItem::STATUS_ON_LOAN]);
            }
        });

        return back()->with('success', 'Peminjaman berhasil dicatat. Kartu biru, kartu merah, dan stempel fisik tetap wajib diproses.');
    }

    public function returnLoan(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $loan = LibraryLoan::with('items.item')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $loan->unit), 403);
        $validated = $request->validate([
            'conditions' => 'required|array',
            'conditions.*' => 'required|in:baik,rusak_ringan,rusak_sedang,rusak_berat,lost',
            'return_notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($loan, $validated, $request) {
            foreach ($loan->items as $loanItem) {
                $condition = $validated['conditions'][$loanItem->id] ?? 'baik';
                $loanItem->update(['returned_at' => today(), 'return_condition' => $condition, 'return_notes' => $validated['return_notes'] ?? null, 'status' => 'returned']);
                $loanItem->item?->update([
                    'status' => $condition === 'lost' ? LibraryItem::STATUS_LOST : (in_array($condition, ['rusak_sedang', 'rusak_berat'], true) ? LibraryItem::STATUS_MAINTENANCE : LibraryItem::STATUS_AVAILABLE),
                    'condition' => $condition,
                ]);
            }
            $loan->update(['status' => LibraryLoan::STATUS_RETURNED, 'returned_at' => today()]);
        });

        return back()->with('success', 'Pengembalian dicatat.');
    }
}
