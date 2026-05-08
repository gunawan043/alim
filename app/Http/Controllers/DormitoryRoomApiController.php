<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryResident;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DormitoryRoomApiController extends Controller
{
    /**
     * Get residents in the same dormitory but NOT yet in this room.
     * (unassigned to this specific room — they may be in another room or no room yet)
     */
    public function availableResidents(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->find($roomUuid);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Kamar tidak ditemukan.'], 404);
        }

        // Students already assigned to THIS room (active)
        $assignedInRoomIds = DormitoryResident::where('room_id', $roomUuid)
            ->where('is_active', true)
            ->pluck('student_id');

        $query = DormitoryResident::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->whereNotIn('student_id', $assignedInRoomIds)
            ->with('student:id,name,nisn,gender,birth_place,birth_date');

        if ($request->filled('q')) {
            $query->whereHas('student', fn($sq) => $sq
                ->where('name', 'like', '%' . $request->q . '%')
                ->orWhere('nisn', 'like', '%' . $request->q . '%')
            );
        }

        $residents = $query->orderBy('student.name')->get();

        $data = $residents->map(fn($r) => [
            'id'           => $r->id,
            'student_id'   => $r->student_id,
            'student_name' => $r->student?->name ?? '-',
            'nisn'         => $r->student?->nisn ?? '-',
            'gender'       => $r->student?->gender ?? '-',
            'bed_number'   => $r->bed_number,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Add a resident to a room.
     */
    public function addResident(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $request->validate([
            'resident_id' => 'required|exists:dormitory_residents,id',
        ]);

        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->find($roomUuid);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Kamar tidak ditemukan.'], 404);
        }

        $resident = DormitoryResident::where('id', $request->resident_id)
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->first();

        if (!$resident) {
            return response()->json(['success' => false, 'message' => 'Penghuni tidak ditemukan.'], 404);
        }

        // Check capacity
        $currentCount = DormitoryResident::where('room_id', $roomUuid)->where('is_active', true)->count();
        if ($currentCount >= $room->capacity) {
            return response()->json([
                'success' => false,
                'message' => "Kamar {$room->code} sudah penuh ({$room->capacity} penghuni).",
            ], 422);
        }

        // If already in another room, remove them from there first
        DormitoryResident::where('student_id', $resident->student_id)
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->where('id', '!=', $resident->id)
            ->update(['is_active' => false]);

        // Assign to this room
        $resident->update(['room_id' => $roomUuid, 'is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => "Penghuni berhasil dimasukkan ke kamar {$room->code}.",
            'data'    => $resident,
        ]);
    }

    /**
     * Bulk add residents to a room.
     */
    public function bulkAddResidents(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $request->validate([
            'resident_ids' => 'required|array|min:1',
            'resident_ids.*' => 'exists:dormitory_residents,id',
        ], [
            'resident_ids.required' => 'Pilih minimal satu penghuni.',
            'resident_ids.*.exists' => 'ID penghuni tidak valid.',
        ]);

        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->find($roomUuid);
        if (!$room) {
            return response()->json(['success' => false, 'message' => 'Kamar tidak ditemukan.'], 404);
        }

        $currentCount = DormitoryResident::where('room_id', $roomUuid)->where('is_active', true)->count();
        $available = $room->capacity - $currentCount;

        if ($available <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Kamar {$room->code} sudah penuh.",
            ], 422);
        }

        $added = 0;
        $skipped = 0;

        foreach ($request->resident_ids as $residentId) {
            if ($added >= $available) {
                $skipped++;
                continue;
            }

            $resident = DormitoryResident::where('id', $residentId)
                ->where('dormitory_id', $asramaUuid)
                ->where('is_active', true)
                ->first();

            if (!$resident) continue;

            // If already in another room, deactivate that
            DormitoryResident::where('student_id', $resident->student_id)
                ->where('dormitory_id', $asramaUuid)
                ->where('is_active', true)
                ->where('id', '!=', $residentId)
                ->update(['is_active' => false]);

            $resident->update(['room_id' => $roomUuid, 'is_active' => true]);
            $added++;
        }

        $msg = $added > 0
            ? "{$added} penghuni berhasil dimasukkan ke kamar {$room->code}."
            : 'Tidak ada penghuni yang bisa ditambahkan — kamar sudah penuh.';

        if ($skipped > 0) {
            $msg .= " {$skipped} sisa dilewati karena kamar sudah penuh.";
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * Remove a resident from a room (set room_id to null).
     */
    public function removeResident(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $request->validate([
            'resident_id' => 'required|exists:dormitory_residents,id',
        ]);

        $resident = DormitoryResident::where('id', $request->resident_id)
            ->where('room_id', $roomUuid)
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->first();

        if (!$resident) {
            return response()->json(['success' => false, 'message' => 'Penghuni tidak ditemukan di kamar ini.'], 404);
        }

        $resident->update(['room_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Penghuni berhasil dikeluarkan dari kamar.',
        ]);
    }
}
