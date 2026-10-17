<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Requests\Sarpras\BookingRejectRequest;
use App\Http\Requests\Sarpras\BookingStoreRequest;
use App\Models\AssetRoom;
use App\Models\RoomBooking;
use App\Models\School;
use Illuminate\Http\Request;

class SarprasBookingController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    public function index(Request $request)
    {
        $query = RoomBooking::with(['room.building', 'room.school', 'user', 'approver']);

        if (! $this->canViewAll($request)) {
            $query->whereHas('room', fn ($q) => $this->scopeToSchool($request, $q));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->date_to);
        }

        $bookings = $query->orderBy('booking_date', 'desc')->paginate(15)->withQueryString();
        $rooms = AssetRoom::where('is_active', true)->orderBy('room_name')->get();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.booking.index', compact('bookings', 'rooms', 'schools'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->where('is_bookable', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        return view('sarpras.booking.create', compact('rooms'));
    }

    public function store(BookingStoreRequest $request)
    {
        $validated = $request->validated();

        $room = AssetRoom::findOrFail($validated['room_id']);

        $validated['booked_by'] = auth()->id();
        $validated['work_unit_id'] = $room->work_unit_id;
        $validated['school_id'] = $room->school_id;

        // Check konflik
        $conflict = RoomBooking::where('room_id', $validated['room_id'])
            ->where('booking_date', $validated['booking_date'])
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })->exists();

        if ($conflict) {
            return back()->withInput()->with('error', 'Ruang sudah dibooking pada jam tersebut.');
        }

        $validated['status'] = $room->booking_requires_approval ? 'pending' : 'approved';

        if ($validated['status'] === 'approved') {
            $validated['approved_by'] = auth()->id();
            $validated['approved_at'] = now();
        }

        RoomBooking::create($validated);
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.booking.index')
            ->with('success', 'Request booking ruangan berhasil diajukan.');
    }

    public function show(Request $request, string $id)
    {
        $booking = RoomBooking::with(['room.building', 'user', 'approver'])->findOrFail($id);
        $this->authorizeBookingAccess($booking, $request);

        return view('sarpras.booking.show', compact('booking'));
    }

    public function approve(Request $request, string $id)
    {
        $booking = RoomBooking::findOrFail($id);
        $this->authorizeBookingAccess($booking, $request);

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Booking sudah diproses.');
        }

        $booking->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Booking ruangan berhasil disetujui.');
    }

    public function reject(BookingRejectRequest $request, string $id)
    {
        $booking = RoomBooking::findOrFail($id);
        $this->authorizeBookingAccess($booking, $request);

        $validated = $request->validated();

        $booking->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Booking ruangan ditolak.');
    }

    public function cancel(Request $request, string $id)
    {
        $booking = RoomBooking::findOrFail($id);

        if ($booking->booked_by !== auth()->id()) {
            abort(403);
        }

        $booking->update(['status' => 'cancelled']);
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.booking.index')
            ->with('success', 'Booking berhasil dibatalkan.');
    }

    public function complete(Request $request, string $id)
    {
        $booking = RoomBooking::findOrFail($id);
        $this->authorizeBookingAccess($booking, $request);

        if ($booking->status !== 'approved') {
            return back()->with('error', 'Booking belum disetujui.');
        }

        $validated = $request->validated();

        $booking->update([
            'status' => 'completed',
            'actual_start_time' => $request->actual_start_time,
            'actual_end_time' => $request->actual_end_time,
            'condition_after' => $validated['condition_after'] ?? null,
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Booking ditandai selesai.');
    }
}
