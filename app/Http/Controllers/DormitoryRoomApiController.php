<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DormitoryRoomApiController extends Controller
{
    /**
     * Get residents in the same dormitory but NOT yet in this room.
     * (unassigned to this specific room — they may be in another room or no room yet)
     */
    public function availableResidents(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->find($roomUuid);
        if (! $room) {
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
            $query->whereHas('student', fn ($sq) => $sq
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('nisn', 'like', '%'.$request->q.'%')
            );
        }

        $residents = $query->orderBy('student.name')->get();

        $data = $residents->map(fn ($r) => [
            'id' => $r->id,
            'student_id' => $r->student_id,
            'student_name' => $r->student?->name ?? '-',
            'nisn' => $r->student?->nisn ?? '-',
            'gender' => $r->student?->gender ?? '-',
            'bed_number' => $r->bed_number,
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
        if (! $room) {
            return response()->json(['success' => false, 'message' => 'Kamar tidak ditemukan.'], 404);
        }

        $resident = DormitoryResident::where('id', $request->resident_id)
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->first();

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Penghuni tidak ditemukan.'], 404);
        }

        $added = false;
        $message = '';

        // Deactivate previous room assignment and assign to this room in a transaction
        DB::transaction(function () use ($resident, $roomUuid, $room, &$added, &$message) {
            // Check capacity inside transaction to prevent race condition
            $currentCount = DormitoryResident::where('room_id', $roomUuid)->where('is_active', true)->count();
            if ($currentCount >= $room->capacity) {
                $message = "Kamar {$room->code} sudah penuh ({$room->capacity} penghuni).";
                throw new \Exception($message);
            }

            DormitoryResident::where('student_id', $resident->student_id)
                ->where('dormitory_id', $resident->dormitory_id)
                ->where('is_active', true)
                ->where('id', '!=', $resident->id)
                ->update(['is_active' => false]);

            $resident->update(['room_id' => $roomUuid, 'is_active' => true]);
            $added = true;
        });

        if (! $added) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Penghuni berhasil dimasukkan ke kamar {$room->code}.",
            'data' => $resident,
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
        if (! $room) {
            return response()->json(['success' => false, 'message' => 'Kamar tidak ditemukan.'], 404);
        }

        $added = 0;
        $skipped = 0;

        foreach ($request->resident_ids as $residentId) {
            $resident = DormitoryResident::where('id', $residentId)
                ->where('dormitory_id', $asramaUuid)
                ->where('is_active', true)
                ->first();

            if (! $resident) {
                $skipped++;

                continue;
            }

            $tryAdd = false;
            $tryMsg = '';

            DB::transaction(function () use ($resident, $roomUuid, $room, &$tryAdd, &$tryMsg) {
                $currentCount = DormitoryResident::where('room_id', $roomUuid)->where('is_active', true)->count();
                if ($currentCount >= $room->capacity) {
                    $tryMsg = "Kamar {$room->code} sudah penuh ({$room->capacity} penghuni).";
                    throw new \Exception($tryMsg);
                }

                DormitoryResident::where('student_id', $resident->student_id)
                    ->where('dormitory_id', $resident->dormitory_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $resident->id)
                    ->update(['is_active' => false]);

                $resident->update(['room_id' => $roomUuid, 'is_active' => true]);
                $tryAdd = true;
            });

            if ($tryAdd) {
                $added++;
            } else {
                $skipped++;
            }
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

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Penghuni tidak ditemukan di kamar ini.'], 404);
        }

        $resident->update(['room_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Penghuni berhasil dikeluarkan dari kamar.',
        ]);
    }
}
