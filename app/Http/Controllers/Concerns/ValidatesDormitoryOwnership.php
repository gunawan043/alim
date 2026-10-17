<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicYear;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use Illuminate\Validation\ValidationException;

trait ValidatesDormitoryOwnership
{
    protected function assertDormitoryResident(string $dormitoryId, string $studentId, ?string $academicYearId = null): DormitoryResident
    {
        $academicYearId ??= AcademicYear::where('is_active', true)->value('id');

        $resident = DormitoryResident::query()
            ->where('dormitory_id', $dormitoryId)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->first();

        if (! $resident) {
            throw ValidationException::withMessages([
                'student_id' => 'Santri bukan penghuni aktif asrama ini.',
            ]);
        }

        return $resident;
    }

    protected function assertDormitoryRoom(string $dormitoryId, string $roomId): DormitoryRoom
    {
        $room = DormitoryRoom::where('dormitory_id', $dormitoryId)->find($roomId);

        if (! $room) {
            throw ValidationException::withMessages([
                'room_id' => 'Kamar tidak berada di asrama ini.',
            ]);
        }

        return $room;
    }
}
