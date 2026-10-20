<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AssetRoom;
use App\Models\LibraryUnit;
use App\Models\RoomBooking;
use App\Services\LibraryAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryRoomController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $rooms = AssetRoom::query()
            ->where('school_id', $unit->school_id)
            ->where('is_bookable', true)
            ->where('is_active', true)
            ->orderBy('room_name')->get();
        $bookings = RoomBooking::with(['room:id,room_name', 'user:id,name', 'approver:id,name'])
            ->where('library_unit_id', $unit->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('booking_date')->orderBy('start_time')->limit(200)->get();
        $stats = [
            'today' => RoomBooking::where('library_unit_id', $unit->id)->whereDate('booking_date', today())->count(),
            'pending' => RoomBooking::where('library_unit_id', $unit->id)->where('status', 'pending')->count(),
            'approved' => RoomBooking::where('library_unit_id', $unit->id)->where('status', 'approved')->count(),
        ];

        return view('perpustakaan.rooms.index', compact('userId', 'units', 'unit', 'rooms', 'bookings', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'room_id' => 'required|exists:asset_rooms,id',
            'event_name' => 'required|string|max:191',
            'purpose' => 'required|string|max:2000',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'participants_count' => 'nullable|integer|min:1|max:1000',
            'booking_source' => 'required|in:request,direct',
            'notes' => 'nullable|string|max:2000',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $room = AssetRoom::findOrFail($validated['room_id']);
        abort_unless((string) $room->school_id === (string) $unit->school_id, 422, 'Ruang bukan milik sekolah unit ini.');

        $conflict = RoomBooking::where('room_id', $room->id)
            ->whereDate('booking_date', $validated['booking_date'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();
        abort_if($conflict, 422, 'Ruang sudah dipesan pada jam tersebut.');

        $direct = $validated['booking_source'] === 'direct';
        RoomBooking::create([
            'room_id' => $room->id,
            'library_unit_id' => $unit->id,
            'work_unit_id' => $unit->work_unit_id,
            'school_id' => $unit->school_id,
            'booked_by' => $request->user()->id,
            'purpose' => $validated['purpose'],
            'event_name' => $validated['event_name'],
            'participants_count' => $validated['participants_count'] ?? null,
            'booking_source' => $validated['booking_source'],
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => ($direct || ! $room->booking_requires_approval) ? 'approved' : 'pending',
            'approved_by' => ($direct || ! $room->booking_requires_approval) ? $request->user()->id : null,
            'approved_at' => ($direct || ! $room->booking_requires_approval) ? now() : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Peminjaman ruang dicatat.');
    }

    public function approve(Request $request, string $userId, string $id)
    {
        $booking = RoomBooking::where('library_unit_id', $request->input('library_unit_id'))->findOrFail($id);
        $unit = LibraryUnit::findOrFail($booking->library_unit_id);
        abort_unless($this->access->canManageUnit($request->user(), $unit), 403);
        abort_unless($booking->status === 'pending', 422, 'Booking tidak dalam status menunggu.');
        $booking->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return back()->with('success', 'Peminjaman ruang disetujui.');
    }

    public function reject(Request $request, string $userId, string $id)
    {
        $booking = RoomBooking::where('library_unit_id', $request->input('library_unit_id'))->findOrFail($id);
        $unit = LibraryUnit::findOrFail($booking->library_unit_id);
        abort_unless($this->access->canManageUnit($request->user(), $unit), 403);
        $booking->update(['status' => 'rejected', 'rejection_reason' => $request->input('reason', 'Ditolak koordinator.')]);

        return back()->with('success', 'Peminjaman ruang ditolak.');
    }

    public function complete(Request $request, string $userId, string $id)
    {
        $booking = RoomBooking::where('library_unit_id', $request->input('library_unit_id'))->findOrFail($id);
        $unit = LibraryUnit::findOrFail($booking->library_unit_id);
        abort_unless($this->access->canManageUnit($request->user(), $unit), 403);
        $booking->update(['status' => 'completed', 'actual_end_time' => now()->format('H:i')]);

        return back()->with('success', 'Penggunaan ruang selesai.');
    }
}
