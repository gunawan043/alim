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
    /**
     * TARIK DATA (ala Dapodik): santri aktif berkategori boarding yang BELUM
     * memiliki kamar di asrama ini — siap ditempatkan.
     */
    public function pullableStudents(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = \App\Models\Dormitory::find($asramaUuid);

        if (! $dormitory) {
            return response()->json(['success' => false, 'message' => 'Asrama tidak ditemukan.'], 404);
        }

        $activeYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');

        // Santri yang sudah punya kamar aktif (di asrama mana pun pada TA aktif).
        $assignedIds = \App\Models\DormitoryResident::query()
            ->where('is_active', true)
            ->whereNotNull('room_id')
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->pluck('student_id');

        $query = \App\Models\Student::query()
            ->where('school_id', $dormitory->school_id)
            ->where('status', 'active')
            ->whereNotIn('id', $assignedIds)
            ->where(function ($q) {
                $q->where('boarding_status', 'boarding')
                    ->orWhereNull('boarding_status'); // belum ditentukan → ikut ditarik
            });

        // Cocokkan gender: putra ↔ L, putri ↔ P.
        if (in_array($dormitory->gender, ['putra', 'putri'], true)) {
            $query->where('gender', $dormitory->gender === 'putra' ? 'L' : 'P');
        }

        if ($request->filled('q')) {
            $needle = $request->q;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$needle}%")
                ->orWhere('nisn', 'like', "%{$needle}%")
                ->orWhere('nis', 'like', "%{$needle}%"));
        }

        $students = $query->orderBy('name')->limit(100)->get(['id', 'name', 'nisn', 'nis', 'gender']);

        return response()->json([
            'success' => true,
            'count' => $students->count(),
            'students' => $students,
        ]);
    }

    /**
     * Tempatkan santri (hasil tarik data) ke kamar — satu atau massal.
     */
    public function assignStudents(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = \App\Models\DormitoryRoom::where('dormitory_id', $asramaUuid)->find($roomUuid);

        if (! $room) {
            return response()->json(['success' => false, 'message' => 'Kamar tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $dormitory = \App\Models\Dormitory::find($asramaUuid);

        // Penempatan TERKUNCI pada satuan pendidikan asrama.
        $validIds = \App\Models\Student::whereIn('id', $data['student_ids'])
            ->where('school_id', $dormitory?->school_id)
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($data['student_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat santri dari satuan pendidikan lain — asrama ini hanya untuk '
                    .($dormitory?->school?->name ?? 'satuan pendidikan terkait').'.',
            ], 422);
        }

        // Validasi gender sesuai asrama/kamar.
        $expected = match ($room->gender ?? $dormitory?->gender) {
            'putra' => 'L',
            'putri' => 'P',
            default => null,
        };

        if ($expected) {
            $salah = \App\Models\Student::whereIn('id', $data['student_ids'])
                ->where('gender', '<>', $expected)->exists();

            if ($salah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terdapat santri dengan gender tidak sesuai untuk asrama ini.',
                ], 422);
            }
        }

        $activeYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');

        if (! $activeYearId) {
            return response()->json(['success' => false, 'message' => 'Tidak ada tahun ajaran aktif.'], 422);
        }

        $capacity = (int) ($room->capacity ?? 0);
        $used = \App\Models\DormitoryResident::where('room_id', $room->id)->where('is_active', true)->count();
        $available = max(0, $capacity - $used);

        if (count($data['student_ids']) > $available) {
            return response()->json([
                'success' => false,
                'message' => "Kapasitas kamar tersisa {$available} ranjang (kapasitas {$capacity}, terisi {$used}).",
            ], 422);
        }

        $placed = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $room, $asramaUuid, $activeYearId, &$placed) {
            $nextBed = ((int) \App\Models\DormitoryResident::where('room_id', $room->id)->max('bed_number')) + 1;

            foreach ($data['student_ids'] as $studentId) {
                $existing = \App\Models\DormitoryResident::where('student_id', $studentId)
                    ->where('is_active', true)
                    ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
                    ->first();

                if ($existing) {
                    // Sudah punya kamar → lewati; tanpa kamar → tempatkan sekarang.
                    if ($existing->room_id) { continue; }

                    $existing->update([
                        'room_id' => $room->id,
                        'bed_number' => $nextBed,
                        'check_in_date' => $existing->check_in_date ?: now()->toDateString(),
                    ]);

                    $nextBed++;
                    $placed++;
                    continue;
                }

                \App\Models\DormitoryResident::create([
                    'student_id' => $studentId,
                    'room_id' => $room->id,
                    'dormitory_id' => $asramaUuid,
                    'academic_year_id' => $activeYearId,
                    'bed_number' => $nextBed,
                    'is_active' => true,
                    'check_in_date' => now()->toDateString(),
                ]);

                $nextBed++;
                $placed++;
            }
        });

        return response()->json([
            'success' => true,
            'placed' => $placed,
            'message' => "{$placed} santri berhasil ditempatkan di kamar {$room->name}.",
        ]);
    }

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
    /**
     * Daftar santri AKTIF satuan pendidikan asrama + status boarding (untuk pemilihan).
     */
    public function studentsStatus(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = \App\Models\Dormitory::find($asramaUuid);

        $activeYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');

        $withRoom = \App\Models\DormitoryResident::where('is_active', true)->whereNotNull('room_id')->pluck('student_id');

        $students = \App\Models\Student::where('school_id', $dormitory?->school_id)
            ->where('status', 'active')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('nisn', 'like', '%'.$request->q.'%')))
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'nisn', 'gender', 'boarding_status'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'nisn' => $s->nisn,
                'gender' => $s->gender,
                'boarding_status' => $s->boarding_status,
                'has_room' => $withRoom->contains($s->id),
            ]);

        return response()->json(['success' => true, 'students' => $students]);
    }

    /**
     * Tetapkan status boarding massal (boarding / non-boarding) —
     * fitur pemilihan santri yang tinggal di asrama atau tidak.
     */
    public function updateBoardingStatus(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'status' => 'required|in:boarding,non_boarding',
        ]);

        $dormitory = \App\Models\Dormitory::find($asramaUuid);

        $updated = \App\Models\Student::whereIn('id', $data['student_ids'])
            ->where('school_id', $dormitory?->school_id)
            ->update(['boarding_status' => $data['status']]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'message' => $updated.' santri ditandai '.($data['status'] === 'boarding' ? 'BOARDING (tinggal di asrama)' : 'NON-BOARDING (tidak tinggal di asrama)').'.',
        ]);
    }

    /**
     * BERHENTI MONDOK: tutup penghuni asrama, santri tetap aktif di sekolah.
     */
    public function checkoutResident(Request $request, string $userId, string $asramaUuid, string $residentId)
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $resident = \App\Models\DormitoryResident::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->find($residentId);

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Penghuni aktif tidak ditemukan.'], 404);
        }

        $resident->update([
            'is_active' => false,
            'check_out_date' => now()->toDateString(),
            'check_out_reason' => $data['reason'] ?? 'Berhenti mondok',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Santri berhenti mondok — kamar dikosongkan, status kehadiran sekolah tetap aktif.',
        ]);
    }

    /**
     * AJUKAN mutasi kamar (jejak approval di dormitory_room_moves).
     */
    public function requestMove(Request $request, string $userId, string $asramaUuid, string $residentId)
    {
        $data = $request->validate([
            'to_room_id' => 'required|exists:dormitory_rooms,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $resident = \App\Models\DormitoryResident::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)->find($residentId);

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Penghuni aktif tidak ditemukan.'], 404);
        }

        $toRoom = \App\Models\DormitoryRoom::where('dormitory_id', $asramaUuid)->find($data['to_room_id']);

        if (! $toRoom) {
            return response()->json(['success' => false, 'message' => 'Kamar tujuan tidak ditemukan.'], 404);
        }

        if ((string) $toRoom->id === (string) $resident->room_id) {
            return response()->json(['success' => false, 'message' => 'Kamar tujuan sama dengan kamar saat ini.'], 422);
        }

        $terisi = \App\Models\DormitoryResident::where('room_id', $toRoom->id)->where('is_active', true)->count();

        if ($terisi >= (int) ($toRoom->capacity ?? 0)) {
            return response()->json(['success' => false, 'message' => 'Kamar tujuan penuh ('.$terisi.'/'.$toRoom->capacity.').'], 422);
        }

        $activeYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');

        \App\Models\DormitoryRoomMove::forceCreate([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'student_id' => $resident->student_id,
            'from_room_id' => $resident->room_id,
            'to_room_id' => $toRoom->id,
            'dormitory_id' => $asramaUuid,
            'academic_year_id' => $activeYearId,
            'move_date' => now()->toDateString(),
            'reason' => $data['reason'] ?? null,
            'move_type' => $resident->room_id ? 'rotasi' : 'permintaan',
            'approval_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan mutasi kamar dibuat — menunggu persetujuan.',
        ]);
    }

    /**
     * Daftar mutasi kamar menunggu persetujuan.
     */
    public function pendingMoves(Request $request, string $userId, string $asramaUuid)
    {
        $moves = \App\Models\DormitoryRoomMove::with(['student:id,name', 'fromRoom:id,name,code', 'toRoom:id,name,code'])
            ->where('dormitory_id', $asramaUuid)
            ->where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'student' => $m->student?->name ?? '-',
                'from_room' => $m->fromRoom?->name ?? $m->fromRoom?->code ?? '-',
                'to_room' => $m->toRoom?->name ?? $m->toRoom?->code ?? '-',
                'reason' => $m->reason,
                'tanggal' => $m->move_date,
            ]);

        return response()->json(['success' => true, 'moves' => $moves]);
    }

    /**
     * SETUJUI mutasi kamar → penghuni benar-benar dipindah (ranjang otomatis).
     */
    public function approveMove(Request $request, string $userId, string $asramaUuid, string $moveId)
    {
        $move = \App\Models\DormitoryRoomMove::where('dormitory_id', $asramaUuid)
            ->where('approval_status', 'pending')->find($moveId);

        if (! $move) {
            return response()->json(['success' => false, 'message' => 'Mutasi tidak ditemukan / sudah diproses.'], 404);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($move) {
            $resident = \App\Models\DormitoryResident::where('student_id', $move->student_id)
                ->where('dormitory_id', $move->dormitory_id)
                ->where('is_active', true)->first();

            if (! $resident) {
                $resident = \App\Models\DormitoryResident::create([
                    'student_id' => $move->student_id,
                    'room_id' => $move->to_room_id,
                    'dormitory_id' => $move->dormitory_id,
                    'academic_year_id' => $move->academic_year_id,
                    'bed_number' => 1,
                    'is_active' => true,
                    'check_in_date' => now()->toDateString(),
                ]);
            } else {
                $nextBed = ((int) \App\Models\DormitoryResident::where('room_id', $move->to_room_id)->max('bed_number')) + 1;

                $resident->update([
                    'room_id' => $move->to_room_id,
                    'bed_number' => $nextBed,
                ]);
            }

            $move->update([
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Mutasi kamar disetujui & penghuni dipindahkan.']);
    }

    /**
     * TOLAK mutasi kamar (penghuni tidak berpindah).
     */
    public function rejectMove(Request $request, string $userId, string $asramaUuid, string $moveId)
    {
        $data = $request->validate(['notes' => 'nullable|string|max:255']);

        $move = \App\Models\DormitoryRoomMove::where('dormitory_id', $asramaUuid)
            ->where('approval_status', 'pending')->find($moveId);

        if (! $move) {
            return response()->json(['success' => false, 'message' => 'Mutasi tidak ditemukan / sudah diproses.'], 404);
        }

        $move->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Mutasi kamar ditolak.']);
    }

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

        $resident->update(['room_id' => null, 'bed_number' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Penghuni berhasil dikeluarkan dari kamar.',
        ]);
    }
}
