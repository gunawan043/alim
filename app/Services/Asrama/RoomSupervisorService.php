<?php

namespace App\Services\Asrama;

use App\Models\AcademicYear;
use App\Models\DormitoryRoom;
use App\Models\RoomSupervisor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Wali Kamar (Room Supervisor) management service.
 *
 * Handles assignment lifecycle: creation, replacement, end-of-assignment,
 * and cross-room guardrails (only one active supervisor per room per year).
 */
class RoomSupervisorService
{
    /**
     * Create a new active supervisor assignment.
     * If the room already has an active supervisor for the same academic year,
     * end the previous assignment before creating the new one.
     *
     * @throws RuntimeException
     */
    public function assign(
        string $userId,
        string $roomId,
        ?string $academicYearId = null,
        ?string $startDate = null,
        ?string $decreeId = null,
        ?string $notes = null,
        ?string $actorId = null,
    ): RoomSupervisor {
        return DB::transaction(function () use (
            $userId,
            $roomId,
            $academicYearId,
            $startDate,
            $decreeId,
            $notes,
            $actorId,
        ) {
            $academicYearId ??= AcademicYear::where('is_active', true)->value('id');
            if (! $academicYearId) {
                throw new RuntimeException('Tidak ada tahun ajaran aktif.');
            }

            $room = DormitoryRoom::findOrFail($roomId);

            $startDate ??= now()->toDateString();

            $existing = RoomSupervisor::where('room_id', $roomId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->first();

            if ($existing && $existing->user_id === $userId) {
                throw new RuntimeException('Pegawai tersebut sudah menjadi Wali Kamar aktif untuk kamar ini.');
            }

            if ($existing) {
                $this->endAssignment($existing, $startDate, 'Digantikan oleh wali kamar lain', $actorId);
            }

            return RoomSupervisor::create([
                'user_id' => $userId,
                'room_id' => $roomId,
                'dormitory_id' => $room->dormitory_id,
                'academic_year_id' => $academicYearId,
                'decree_id' => $decreeId,
                'start_date' => $startDate,
                'end_date' => null,
                'status' => 'active',
                'notes' => $notes,
            ]);
        });
    }

    /**
     * End an existing active assignment (status -> ended, set end_date).
     */
    public function endAssignment(
        RoomSupervisor $supervisor,
        ?string $endDate = null,
        ?string $notes = null,
        ?string $actorId = null,
    ): RoomSupervisor {
        $endDate ??= now()->toDateString();

        if ($supervisor->status !== 'active') {
            return $supervisor;
        }

        $supervisor->update([
            'status' => 'ended',
            'end_date' => $endDate,
            'notes' => $notes
                ? trim(($supervisor->notes ?? '')."\n".$notes)
                : $supervisor->notes,
        ]);

        Log::info('RoomSupervisor ended', [
            'supervisor_id' => $supervisor->id,
            'actor_id' => $actorId,
        ]);

        return $supervisor;
    }

    /**
     * Update an assignment. When changing room or user, enforce the
     * one-active-supervisor-per-room-per-year rule by ending overlapping
     * assignments before persisting.
     *
     * @throws RuntimeException
     */
    public function update(
        RoomSupervisor $supervisor,
        array $data,
        ?string $actorId = null,
    ): RoomSupervisor {
        return DB::transaction(function () use ($supervisor, $data, $actorId) {
            $roomChanged = ($data['room_id'] ?? $supervisor->room_id) !== $supervisor->room_id;
            $userChanged = ($data['user_id'] ?? $supervisor->user_id) !== $supervisor->user_id;

            if ($supervisor->status === 'active' && ($roomChanged || $userChanged)) {
                $existing = RoomSupervisor::where('room_id', $data['room_id'] ?? $supervisor->room_id)
                    ->where('academic_year_id', $data['academic_year_id'] ?? $supervisor->academic_year_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $supervisor->id)
                    ->first();

                if ($existing) {
                    $this->endAssignment($existing, $data['start_date'] ?? null, 'Digantikan oleh wali kamar lain', $actorId);
                }
            }

            if (($data['status'] ?? $supervisor->status) === 'ended' && $supervisor->status !== 'ended') {
                $data['end_date'] = $data['end_date'] ?? now()->toDateString();
            }

            $supervisor->update($data);

            return $supervisor->fresh();
        });
    }
}