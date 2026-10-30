<?php

namespace App\Services\Dashboard;

use App\Models\AcademicYear;
use App\Models\AnnouncementAcknowledgement;
use App\Models\GradeLevel;
use App\Models\HumasAnnouncement;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Konteks bersama seluruh dashboard jabatan:
 * filter (tahun ajaran, semester, unit, jenjang), carousel info, dan broadcast.
 *
 * Semua nilai yang tidak tersedia dikembalikan sebagai null — bukan 0 —
 * supaya tampilan dapat membedakan "tidak ada data" dan "nol".
 */
class DashboardContextService
{
    public const INFO_CATEGORY = 'broadcast';

    /**
     * Resolusi filter dashboard dari query string.
     *
     * @return array{
     *   academic_year_id: ?string, academic_year: ?AcademicYear,
     *   academic_year_name: ?string, semester: ?string,
     *   school_id: ?string, school: ?School, school_locked: bool,
     *   school_ids: array<int, string>, grade_level_id: ?string,
     *   grade_level: ?GradeLevel, grade_levels: Collection, schools: Collection,
     *   academic_years: Collection, period_label: string, updated_at: ?string
     * }
     */
    public function filters(Request $request, User $user): array
    {
        $lockedSchoolId = $this->lockedSchoolId($request, $user);

        $yearOptions = $this->academicYearOptions();
        $activeYear = $yearOptions->firstWhere('is_active', true) ?? $yearOptions->first();

        $requestedYearId = (string) ($request->query('academic_year_id') ?: '');
        $selectedYear = $requestedYearId !== ''
            ? $yearOptions->firstWhere('uid', $requestedYearId) ?? $activeYear
            : $activeYear;

        $semester = (string) ($request->query('semester') ?: ($selectedYear?->semester ?: 'ganjil'));

        $schools = $this->availableSchools($user, $lockedSchoolId);
        $requestedSchoolId = (string) ($request->query('school_id') ?: '');
        $schoolId = $lockedSchoolId
            ?: ($requestedSchoolId !== '' && $schools->contains('id', $requestedSchoolId) ? $requestedSchoolId : null);

        $schoolIds = $schoolId ? [$schoolId] : $schools->pluck('id')->all();

        $gradeLevels = GradeLevel::query()
            ->whereIn('school_id', $schoolIds)
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        $requestedGradeId = (string) ($request->query('grade_level_id') ?: '');
        $gradeLevel = $gradeLevels->firstWhere('id', $requestedGradeId);

        $periodLabel = $selectedYear?->name
            ? $selectedYear->name.' · '.ucfirst($semester)
            : 'Periode belum tersedia';

        return [
            'academic_year_id' => $selectedYear?->uid,
            'academic_year' => $selectedYear?->year,
            'academic_year_name' => $selectedYear?->name,
            'semester' => $semester,
            'school_id' => $schoolId,
            'school' => $schoolId ? $schools->firstWhere('id', $schoolId) : null,
            'school_locked' => (bool) $lockedSchoolId,
            'school_ids' => $schoolIds,
            'grade_level_id' => $gradeLevel?->id,
            'grade_level' => $gradeLevel,
            'grade_levels' => $gradeLevels,
            'schools' => $schools,
            'academic_years' => $yearOptions->pluck('year')->filter()->values(),
            'period_label' => $periodLabel,
            'updated_at' => $this->dataUpdatedAt($schoolIds),
        ];
    }

    /**
     * Tahun ajaran unik per nama (tabel menyimpan satu baris per semester).
     */
    public function academicYearOptions(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get()
            ->groupBy('name')
            ->map(function (Collection $rows, string $name) {
                $primary = $rows->first();

                return (object) [
                    'uid' => $primary->id,
                    'name' => $name,
                    'semester' => $primary->semester,
                    'is_active' => (bool) $primary->is_active,
                    'year' => $primary,
                    'ids' => $rows->pluck('id')->all(),
                ];
            })
            ->sortByDesc(fn ($option) => $option->year->start_date?->timestamp ?? 0)
            ->values();
    }

    /**
     * Carousel info: pengumuman terbit yang memiliki gambar.
     * Sumber gambar: relasi `images` lalu fallback `banner_path`.
     */
    public function infoSlides(User $user, array $filters, int $limit = 5): Collection
    {
        $schoolIds = $filters['school_ids'] ?? [];
        $globalPublisher = $this->isGlobalPublisher($user);

        $items = HumasAnnouncement::query()
            ->with(['images', 'school:id,name'])
            ->published()
            ->where('category', '!=', self::INFO_CATEGORY)
            ->where(function ($query) use ($schoolIds, $globalPublisher) {
                $query->whereNull('school_id');

                if (! $globalPublisher && ! empty($schoolIds)) {
                    $query->orWhereIn('school_id', $schoolIds);
                }
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(30)
            ->get();

        return $items
            ->map(function (HumasAnnouncement $item) {
                $image = $item->images->sortBy('sort_order')->first()?->url
                    ?: $item->banner_url;

                if (! $image) {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'excerpt' => $item->excerpt ?: Str::limit(strip_tags((string) $item->content), 140),
                    'image' => $image,
                    'category' => $item->category,
                    'school' => $item->school?->name,
                    'published_at' => $item->published_at?->toIso8601String(),
                    'url' => route('user.announcements.show', ['userId' => auth()->id(), 'id' => $item->id]),
                    'is_pinned' => (bool) $item->is_pinned,
                ];
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * Broadcast aktif yang ditujukan kepada pengguna dan belum dikonfirmasi.
     */
    public function pendingBroadcasts(User $user, array $filters): Collection
    {
        $schoolIds = $filters['school_ids'] ?? [];
        $global = $this->isGlobalPublisher($user);

        $acknowledged = AnnouncementAcknowledgement::query()
            ->where('reference_type', HumasAnnouncement::class)
            ->where('user_id', $user->id)
            ->pluck('reference_id')
            ->all();

        $classIds = $this->userClassIds($user);

        return HumasAnnouncement::query()
            ->published()
            ->where('category', self::INFO_CATEGORY)
            ->when($classIds !== [], fn ($query) => $query->orWhereIn('study_group_id', $classIds))
            ->where(function ($query) use ($schoolIds, $classIds) {
                $query->where('target_type', 'global');

                if (! empty($schoolIds)) {
                    $query->orWhere(fn ($sub) => $sub->where('target_type', 'school')->whereIn('school_id', $schoolIds));
                    $query->orWhere(fn ($sub) => $sub->where('target_type', 'class')->whereIn('study_group_id', $classIds));
                    $query->orWhere(fn ($sub) => $sub->where('target_type', 'dormitory')->whereIn('school_id', $schoolIds));
                }
            })
            ->where(fn ($query) => $query->whereNull('created_by')->orWhere('created_by', '!=', $user->id))
            ->orderByDesc('published_at')
            ->limit(20)
            ->get()
            ->reject(fn (HumasAnnouncement $item) => in_array($item->id, $acknowledged, true))
            ->map(fn (HumasAnnouncement $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'excerpt' => $item->excerpt,
                'severity' => $item->severity,
                'image' => $item->banner_url,
                'ack_required' => (bool) $item->ack_required,
                'published_at' => $item->published_at?->toIso8601String(),
                'url' => route('user.announcements.show', ['userId' => $user->id, 'id' => $item->id]),
            ])
            ->values();
    }

    /**
     * Catat konfirmasi baca broadcast.
     */
    public function acknowledge(User $user, string $announcementId): bool
    {
        $announcement = HumasAnnouncement::query()
            ->where('category', self::INFO_CATEGORY)
            ->find($announcementId);

        if (! $announcement) {
            return false;
        }

        AnnouncementAcknowledgement::updateOrCreate(
            [
                'reference_type' => HumasAnnouncement::class,
                'reference_id' => $announcement->id,
                'user_id' => $user->id,
                'student_id' => null,
            ],
            ['acknowledged_at' => now()],
        );

        return true;
    }

    public function dataUpdatedAt(array $schoolIds): ?string
    {
        $latest = Student::query()
            ->when(! empty($schoolIds), fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->max('updated_at');

        return $latest ? Carbon::parse($latest)->toIso8601String() : null;
    }

    private function lockedSchoolId(Request $request, User $user): ?string
    {
        $context = $request->attributes->get('schoolContextId');

        if (is_string($context) && $context !== '') {
            return $context;
        }

        return $user->employment?->school_id;
    }

    private function availableSchools(User $user, ?string $lockedSchoolId): Collection
    {
        if ($lockedSchoolId) {
            return School::query()->whereKey($lockedSchoolId)->get(['id', 'name']);
        }

        return School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function isGlobalPublisher(User $user): bool
    {
        return (bool) ($user->isSystemAdmin()
            || $user->isSuperAdmin()
            || $user->hasRole('Humas Personalia'));
    }

    /**
     * Kelas tempat pengguna menjadi wali kelas / guru (untuk target broadcast kelas).
     */
    private function userClassIds(User $user): array
    {
        try {
            return DB::table('study_groups')
                ->where('homeroom_teacher_id', $user->id)
                ->where('is_active', 1)
                ->pluck('id')
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
