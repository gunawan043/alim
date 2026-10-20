<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LibraryItem;
use App\Models\LibraryLoan;
use App\Models\LibraryMember;
use App\Models\LibraryVisit;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;

class LibraryDashboardController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        abort_unless($this->access->hasLibraryRole($user) || $this->access->unitsFor($user)->isNotEmpty(), 403);

        $units = $this->access->unitsFor($user);
        $unitIds = $units->pluck('id');
        $year = AcademicYear::where('is_active', true)->first();

        $stats = [
            'titles' => \App\Models\LibraryTitle::whereIn('school_id', $units->pluck('school_id'))->count(),
            'items' => LibraryItem::whereIn('library_unit_id', $unitIds)->count(),
            'available' => LibraryItem::whereIn('library_unit_id', $unitIds)->where('status', LibraryItem::STATUS_AVAILABLE)->count(),
            'members' => LibraryMember::whereIn('library_unit_id', $unitIds)->where('status', LibraryMember::STATUS_ACTIVE)->count(),
            'loans' => LibraryLoan::whereIn('library_unit_id', $unitIds)->where('status', LibraryLoan::STATUS_ACTIVE)->count(),
            'visits_today' => LibraryVisit::whereIn('library_unit_id', $unitIds)->whereDate('visit_date', today())->count(),
        ];

        $recentLoans = LibraryLoan::with(['member.student:id,name,nis', 'member.user:id,name', 'items.item.title'])
            ->whereIn('library_unit_id', $unitIds)->latest()->limit(8)->get();
        $recentVisits = LibraryVisit::with(['member.student:id,name', 'member.user:id,name'])
            ->whereIn('library_unit_id', $unitIds)->latest()->limit(8)->get();

        return view('perpustakaan.dashboard', compact('userId', 'units', 'year', 'stats', 'recentLoans', 'recentVisits'));
    }
}
