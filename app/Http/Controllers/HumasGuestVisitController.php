<?php

namespace App\Http\Controllers;

use App\Models\HumasGuestVisit;
use Illuminate\Http\Request;

class HumasGuestVisitController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $schoolId = $request->attributes->get('schoolContextId');
        $query = HumasGuestVisit::with('officer:id,name')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('check_in_at', $request->date))
            ->latest('check_in_at');
        $visits = $query->paginate(25)->withQueryString();
        $base = HumasGuestVisit::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $stats = [
            'today' => (clone $base)->whereDate('check_in_at', today())->count(),
            'inside' => (clone $base)->where('status', 'checked_in')->count(),
            'done' => (clone $base)->whereDate('check_in_at', today())->where('status', 'checked_out')->count(),
        ];

        return view('humas.guest-visits.index', compact('userId', 'visits', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeHumas($request);
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:150',
            'institution' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'person_to_meet' => 'nullable|string|max:150',
            'purpose' => 'required|string|max:200',
            'notes' => 'nullable|string|max:2000',
        ]);
        HumasGuestVisit::create($validated + [
            'school_id' => $request->attributes->get('schoolContextId'),
            'status' => 'checked_in',
            'check_in_at' => now(),
            'officer_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Tamu berhasil dicatat dan check-in.');
    }

    public function checkout(Request $request, string $userId, string $id)
    {
        $this->authorizeHumas($request);
        $visit = HumasGuestVisit::when($request->attributes->get('schoolContextId'), fn ($q, $schoolId) => $q->where('school_id', $schoolId))->findOrFail($id);
        abort_if($visit->status === 'checked_out', 422, 'Tamu sudah check-out.');
        $visit->update(['status' => 'checked_out', 'check_out_at' => now()]);

        return back()->with('success', 'Check-out tamu dicatat.');
    }

    private function authorizeHumas(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403);
    }
}
