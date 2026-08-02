<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DormitoryResident;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class StudentLookupService
{
    public const CACHE_TTL = 300; // 5 minutes

    /**
     * Search Academic students for dormitory assignment.
     *
     * Filters out students already assigned to the given dormitory+year,
     * excludes inactive/graduate/dropped/transfer_out students,
     * and includes dormitory-specific info if student is assigned elsewhere.
     *
     * @param  string  $query  Search term (name, nisn, nik)
     * @param  string|null  $dormitoryId  Limit to students from same school as this dormitory (optional)
     * @param  string|null  $academicYearId  Filter by academic year
     * @param  int  $limit  Max results
     */
    public function search(string $query, ?string $dormitoryId = null, ?string $academicYearId = null, int $limit = 20): Collection
    {
        $activeYears = AcademicYear::pluck('id')->toArray();
        $yearId = $academicYearId ?? (AcademicYear::where('is_active', true)->value('id'));

        return Cache::remember(
            sprintf('student_search:%s:%s:%d:%d', md5($query), $yearId ?? 'all', $limit, $dormitoryId ?? 'none'),
            self::CACHE_TTL,
            function () use ($query, $dormitoryId, $yearId, $limit) {
                $queryObj = Student::where('status', 'active')
                    ->where(fn ($sq) => $sq
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('nisn', 'like', "%{$query}%")
                        ->orWhere('nik', 'like', "%{$query}%")
                    )
                    ->limit($limit);

                // If dormitory belongs to a school, scope students to that school
                if ($dormitoryId) {
                    $schoolId = \DB::table('dormitories')
                        ->where('id', $dormitoryId)
                        ->value('school_id');
                    if ($schoolId) {
                        $queryObj->where('school_id', $schoolId);
                    }
                }

                $students = $queryObj->get(['id', 'name', 'nisn', 'nis', 'gender', 'birth_place', 'birth_date', 'status', 'school_id']);

                if ($students->isEmpty()) {
                    return $students;
                }

                $studentIds = $students->pluck('id')->toArray();
                $assignedRecords = DormitoryResident::whereIn('student_id', $studentIds)
                    ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                    ->where('is_active', true)
                    ->with(['room:id,name,code', 'dormitory:id,name'])
                    ->get();

                return $students->map(function ($student) use ($assignedRecords) {
                    $assignment = $assignedRecords->firstWhere('student_id', $student->id);
                    $room = $assignment?->room;

                    return (object) [
                        'id' => $student->id,
                        'name' => $student->name,
                        'nisn' => $student->nisn,
                        'nis' => $student->nis,
                        'gender' => $student->gender,
                        'gender_text' => $student->gender_text,
                        'birth_place' => $student->birth_place,
                        'birth_date' => $student->birth_date?->format('d/m/Y'),
                        'status' => $student->status,
                        'school_id' => $student->school_id,
                        'is_assigned' => (bool) $assignment,
                        'assigned_dormitory' => $assignment?->dormitory?->name ?? null,
                        'assigned_room' => $room?->code ?? null,
                        'assigned_bed' => $assignment?->bed_number ?? null,
                        'room_id' => $room?->id ?? null,
                        'room_name' => $room?->name ?? null,
                    ];
                });
            }
        );
    }

    /**
     * Get full read-only Academic profile for a student.
     */
    public function getProfile(string $studentId): ?object
    {
        return Cache::remember(
            sprintf('student_profile:%s', $studentId),
            self::CACHE_TTL,
            function () use ($studentId) {
                $student = Student::with([
                    'school:id,name',
                    'currentClassHistory.studyGroup.gradeLevel',
                ])
                    ->find($studentId);

                if (! $student) {
                    return null;
                }

                return (object) [
                    // Identity
                    'id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'nis' => $student->nis,
                    'nik' => $student->nik,
                    'gender' => $student->gender,
                    'gender_text' => $student->gender_text,
                    'religion' => $student->religion,
                    'birth_place' => $student->birth_place,
                    'birth_date' => $student->birth_date?->format('Y-m-d'),
                    'birth_date_display' => $student->birth_date?->format('d/m/Y'),
                    'special_needs' => $student->special_needs,

                    // Address
                    'full_address' => $student->full_address,
                    'phone' => $student->phone,
                    'mobile_phone' => $student->mobile_phone,
                    'email' => $student->email,
                    'residence_type' => $student->residence_type,

                    // Parents / Guardian
                    'father' => [
                        'name' => $student->father_name,
                        'birth_year' => $student->father_birth_year,
                        'education' => $student->father_education,
                        'occupation' => $student->father_occupation,
                    ],
                    'mother' => [
                        'name' => $student->mother_name,
                        'birth_year' => $student->mother_birth_year,
                        'education' => $student->mother_education,
                        'occupation' => $student->mother_occupation,
                    ],
                    'guardian' => [
                        'name' => $student->guardian_name,
                        'birth_year' => $student->guardian_birth_year,
                        'education' => $student->guardian_education,
                        'occupation' => $student->guardian_occupation,
                    ],

                    // Academic
                    'status' => $student->status,
                    'status_text' => $student->status_text,
                    'entry_date' => $student->entry_date?->format('Y-m-d'),
                    'entry_grade_level' => $student->entry_grade_level,
                    'graduation_year' => $student->graduation_year,
                    'graduation_date' => $student->graduation_date?->format('Y-m-d'),
                    'alumni_year' => $student->alumni_year ?? null,

                    // Current school & class
                    'school' => $student->school?->name ?? null,
                    'current_class' => $student->currentClassHistory?->studyGroup?->full_name ?? $student->studyGroups->first()?->studyGroup?->name ?? null,
                    'current_section' => $student->studyGroups->first()?->studyGroup->name ?? null,

                    // Photo
                    'photo_url' => $student->photo_url,
                ];
            }
        );
    }

    /**
     * Check if a student is already assigned as an active resident.
     */
    public function getActiveAssignment(?string $studentId, ?string $dormitoryId = null, ?string $academicYearId = null): ?DormitoryResident
    {
        if (! $studentId) {
            return null;
        }

        $query = DormitoryResident::where('student_id', $studentId)
            ->where('is_active', true);

        if ($dormitoryId) {
            $query->where('dormitory_id', $dormitoryId);
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->with(['dormitory:id,name', 'room:id,code', 'academicYear:id,name'])->first();
    }

    /**
     * Find all dormitories where this student currently has an active assignment (across all years).
     *
     * @return Collection<DormitoryResident>
     */
    public function getAllActiveAssignments(string $studentId): Collection
    {
        return DormitoryResident::where('student_id', $studentId)
            ->where('is_active', true)
            ->with(['dormitory:id,name', 'room:id,code', 'academicYear:id,name'])
            ->get();
    }

    /**
     * Validate whether a student is eligible for dormitory assignment.
     *
     * Rules:
     * - Must be a valid Student record
     * - Must have status = 'active' (not graduate/dropped/inactive)
     * - Must not have an active assignment in the target dormitory+year
     *
     * @return object{valid: bool, student: object|null, error: string|null}
     */
    public function validateAssignment(string $studentId, ?string $dormitoryId, ?string $academicYearId): object
    {
        $student = Student::find($studentId);

        if (! $student) {
            return (object) ['valid' => false, 'student' => null, 'error' => 'Santri tidak ditemukan dalam sistem akademik.'];
        }

        if ($student->status !== 'active') {
            return (object) ['valid' => false, 'student' => null, 'error' => "Santri berstatus '{$student->status_text}' dan tidak dapat ditugaskan ke asrama."];
        }

        // Check for existing assignment in this dormitory + year
        $existing = DormitoryResident::where('student_id', $studentId)
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->first();

        if ($existing) {
            $msg = 'Santri ini sudah terdaftar sebagai penghuni aktif di ';
            if ($dormitoryId && $existing->dormitory_id === $dormitoryId) {
                $roomCode = $existing->room ? $existing->room->code : '-';
                $msg .= "{$existing->dormitory->name} (Kamar: {$roomCode}).";
            } elseif ($academicYearId && $existing->academic_year_id === $academicYearId) {
                $msg .= "{$existing->dormitory->name} pada tahun ajaran yang sama.";
            } else {
                $yearName = $existing->academicYear ? $existing->academicYear->name : '-';
                $msg .= "{$existing->dormitory->name} (tahun ajaran: {$yearName}).";
            }

            return (object) ['valid' => false, 'student' => null, 'error' => $msg];
        }

        return (object) ['valid' => true, 'student' => $student, 'error' => null];
    }

    /**
     * Check if a room belongs to the specified dormitory.
     */
    public function roomBelongsToDormitory(string $roomId, string $dormitoryId): bool
    {
        return \DB::table('dormitory_rooms')
            ->where('id', $roomId)
            ->where('dormitory_id', $dormitoryId)
            ->exists();
    }

    /**
     * Get available rooms in a dormitory, optionally filtered by wing.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function getAvailableRooms(string $dormitoryId, ?string $wingId = null): \Illuminate\Support\Collection
    {
        return \DB::table('dormitory_rooms')
            ->select(
                'id',
                'code',
                'name',
                'wing_id',
                'capacity',
                'room_type',
                'is_active',
                \DB::raw('(SELECT COUNT(*) FROM dormitory_residents WHERE dormitory_residents.room_id = dormitory_rooms.id AND is_active = TRUE) as current_occupancy')
            )
            ->where('dormitory_id', $dormitoryId)
            ->when($wingId, fn ($q) => $q->where('wing_id', $wingId))
            ->where('is_active', true)
            ->whereColumn('current_occupancy', '<', 'capacity')
            ->orderBy('wing_id')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get occupied beds in a specific room (so new assignments avoid conflicts).
     *
     * @return array<int|string, int>
     */
    public function getOccupiedBeds(string $roomId): array
    {
        return DormitoryResident::where('room_id', $roomId)
            ->where('is_active', true)
            ->whereNotNull('bed_number')
            ->pluck('bed_number')
            ->toArray();
    }

    /**
     * Invalidate cache for a student record.
     */
    public function invalidateCache(string $studentId): void
    {
        Cache::forget(sprintf('student_profile:%s', $studentId));
    }
}
