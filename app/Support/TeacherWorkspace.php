<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\GtkWorkUnit;
use App\Models\School;
use App\Models\Student;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;

/**
 * Satu sumber kebenaran untuk pertanyaan "GTK ini mengajar di mana?".
 *
 * Guru tidak selalu ber-role "Satuan Pendidikan" dan tidak selalu ditempatkan
 * pada satuan kerja sekolah (mis. Kepala Asrama yang juga mengampu mapel, atau
 * GTK Departemen yang mengajar di beberapa sekolah). Karena itu menu guru dan
 * konteks sekolah harus ditentukan dari SK mengajar aktif — bukan dari role,
 * bukan dari penempatan kerja.
 */
class TeacherWorkspace
{
    /** Naik bila aturan penentuan sekolah berubah (lihat AppServiceProvider). */
    public const CACHE_VERSION = 1;

    /**
     * ID sekolah tempat user punya penugasan mengajar / buku nilai aktif
     * pada tahun ajaran aktif. Urut sesuai prioritas:
     *   1. sekolah penempatan kerja (gtk_work_unit),
     *   2. sekolah pada SK mengajar,
     *   3. sekolah pada buku nilai.
     *
     * @return array<int, string>
     */
    public static function schoolIds(?string $userId = null, ?string $academicYearId = null): array
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return [];
        }

        $academicYearId ??= AcademicYear::query()->where('is_active', true)->value('id');

        $teachingSchoolIds = TeachingAssignment::query()
            ->where('teacher_id', $userId)
            ->where('status', 'active')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('school_id')
            ->filter()
            ->unique()
            ->values();

        $bookSchoolIds = TeacherAdminBook::query()
            ->where('teacher_id', $userId)
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('school_id')
            ->filter()
            ->unique()
            ->values();

        if ($teachingSchoolIds->isEmpty() && $bookSchoolIds->isEmpty()) {
            return [];
        }

        $teachingSchoolIds = $teachingSchoolIds
            ->merge($bookSchoolIds)
            ->unique()
            ->values();

        // Sekolah penempatan kerja lebih dulu supaya guru yang memang bertugas
        // di satu sekolah tetap melihat sekolahnya, bukan sekolah lain yang
        // kebetulan juga memuat SK mengajarnya.
        $placementSchoolIds = GtkWorkUnit::query()
            ->where('user_id', $userId)
            ->join('work_units', 'work_units.id', '=', 'gtk_work_unit.work_unit_id')
            ->join('schools', 'schools.work_unit_id', '=', 'work_units.id')
            ->where('work_units.is_active', true)
            ->where('schools.is_active', true)
            ->orderByDesc('gtk_work_unit.is_primary')
            ->pluck('schools.id');

        return $teachingSchoolIds->isEmpty()
            ? []
            : $placementSchoolIds
                ->filter(fn ($id) => $teachingSchoolIds->contains($id))
                ->merge($teachingSchoolIds)
                ->unique()
                ->values()
                ->all();
    }

    /**
     * Sekolah yang dipakai sebagai konteks untuk GTK yang mengajar tetapi
     * ditempatkan di luar satuan pendidikan.
     */
    public static function preferredSchoolId(?string $userId = null, ?string $academicYearId = null): ?string
    {
        return self::schoolIds($userId, $academicYearId)[0] ?? null;
    }

    /**
     * Apakah user punya peran mengajar aktif (SK mengajar atau buku nilai)
     * pada tahun ajaran aktif. Inilah syarat tampilnya Menu Guru.
     */
    public static function hasTeachingRole(?string $userId = null, ?string $academicYearId = null): bool
    {
        $userId ??= auth()->id();

        return $userId && self::schoolIds($userId, $academicYearId) !== [];
    }

    /**
     * Konteks sekolah untuk pengguna yang ditempatkan di luar satuan pendidikan.
     *
     * Hanya berisi data milik guru itu sendiri (rombel yang diampu dan
     * santrinya) supaya halaman menu guru tidak kosong. Mengembalikan null bila
     * semua sekolah tujuannya sudah terlihat lewat konteks sekolah biasa.
     *
     * @return array{school_id: string, school_name: string, study_group_ids: array<int, string>, student_ids: array<int, string>}|null
     */
    public static function outsideSchoolContext(?string $userId = null, ?string $defaultSchoolId = null, ?string $academicYearId = null): ?array
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return null;
        }

        $schoolIds = self::schoolIds($userId, $academicYearId);

        if ($schoolIds === []) {
            return null;
        }

        $academicYearId ??= AcademicYear::query()->where('is_active', true)->value('id');

        $teachingSchoolIds = TeachingAssignment::query()
            ->where('teacher_id', $userId)
            ->where('status', 'active')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('school_id')
            ->filter()
            ->unique();

        $outsideSchoolIds = collect($schoolIds)
            ->reject(fn ($id) => $defaultSchoolId !== null && $id === $defaultSchoolId)
            ->filter(fn ($id) => $teachingSchoolIds->contains($id))
            ->values();

        if ($outsideSchoolIds->isEmpty()) {
            return null;
        }

        $school = School::query()->find($outsideSchoolIds->first());

        if (! $school) {
            return null;
        }

        $studyGroupIds = TeachingAssignment::query()
            ->where('teacher_id', $userId)
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('study_group_id')
            ->filter()
            ->unique()
            ->values();

        $studentIds = Student::withoutGlobalScope('school_context')
            ->whereHas('classHistories', fn ($q) => $q
                ->whereIn('study_group_id', $studyGroupIds)
                ->when($academicYearId, fn ($q2) => $q2->where('academic_year_id', $academicYearId))
                ->where('is_active', true))
            ->pluck('id')
            ->unique()
            ->values();

        return [
            'school_id' => $school->id,
            'school_name' => $school->name,
            'study_group_ids' => $studyGroupIds->all(),
            'student_ids' => $studentIds->all(),
        ];
    }
}
