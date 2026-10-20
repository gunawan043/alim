<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LibraryItem;
use App\Models\LibraryLoan;
use App\Models\LibraryLoanItem;
use App\Models\LibraryMember;
use App\Models\LibraryUnit;
use App\Models\LibraryVisit;
use App\Services\LibraryAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LibraryReportController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $tab = $request->input('tab', 'summary');
        $year = AcademicYear::where('is_active', true)->first();
        $month = $request->input('month', now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();
        $rangeStart = $tab === 'annual' && $year ? $year->start_date : $monthStart;
        $rangeEnd = $tab === 'annual' && $year ? $year->end_date : $monthEnd;

        $loansQuery = LibraryLoan::with(['member.student:id,name,nis', 'member.user:id,name', 'items.item.title:id,title'])
            ->where('library_unit_id', $unit->id)->whereBetween('loan_date', [$rangeStart, $rangeEnd]);
        $loans = (clone $loansQuery)->latest('loan_date')->limit(300)->get();
        $loanItems = LibraryLoanItem::with('item.title:id,title,author')->whereHas('loan', fn ($q) => $q->where('library_unit_id', $unit->id)->whereBetween('loan_date', [$rangeStart, $rangeEnd]))->get();
        $topTitles = $loanItems->groupBy(fn ($row) => $row->item?->title?->id)->filter(fn ($rows, $id) => $id)->map(function ($rows) {
            return ['title' => $rows->first()->item?->title?->title, 'author' => $rows->first()->item?->title?->author, 'count' => $rows->count()];
        })->sortByDesc('count')->take(10)->values();
        $topMembers = $loans->groupBy('library_member_id')->map(function ($rows) {
            return ['member' => $rows->first()->member, 'count' => $rows->count()];
        })->sortByDesc('count')->take(10)->values();
        $visits = LibraryVisit::where('library_unit_id', $unit->id)->whereBetween('visit_date', [$rangeStart, $rangeEnd]);
        $visitCount = (clone $visits)->count();
        $monthly = collect(range(1, 12))->map(function ($number) use ($unit, $year) {
            $date = $year ? Carbon::parse($year->start_date)->startOfMonth()->addMonths($number - 1) : now()->startOfYear()->addMonths($number - 1);
            return ['label' => $date->format('M'), 'loans' => LibraryLoan::where('library_unit_id', $unit->id)->whereYear('loan_date', $date->year)->whereMonth('loan_date', $date->month)->count(), 'visits' => LibraryVisit::where('library_unit_id', $unit->id)->whereYear('visit_date', $date->year)->whereMonth('visit_date', $date->month)->count()];
        });
        $stats = [
            'loans' => (clone $loansQuery)->count(),
            'active' => LibraryLoan::where('library_unit_id', $unit->id)->where('status', LibraryLoan::STATUS_ACTIVE)->count(),
            'visits' => $visitCount,
            'members' => LibraryMember::where('library_unit_id', $unit->id)->where('status', LibraryMember::STATUS_ACTIVE)->count(),
            'items' => LibraryItem::where('library_unit_id', $unit->id)->where('status', '!=', LibraryItem::STATUS_WITHDRAWN)->count(),
            'overdue' => LibraryLoan::where('library_unit_id', $unit->id)->where('status', LibraryLoan::STATUS_ACTIVE)->whereDate('due_date', '<', today())->count(),
        ];

        return view('perpustakaan.reports.index', compact('userId', 'units', 'unit', 'tab', 'year', 'month', 'loans', 'topTitles', 'topMembers', 'monthly', 'stats', 'rangeStart', 'rangeEnd'));
    }
}
