<?php

namespace App\Services\ArabicName;

use App\Models\GtkEmployment;
use App\Models\Student;
use App\Models\User;
use App\Services\ArabicName\Contracts\ArabicNameGeneratorContract;
use App\Services\SchoolGroupService;

/**
 * Import nama Arab:
 *  - Santri dicocokkan dengan **NIS** (fallback **NISN**), dalam satuan pendidikan.
 *  - GTK dicocokkan dengan **NUPY** pada data kepegawaian.
 *
 * Juga menyediakan pengisian otomatis (generator) untuk nama Arab yang kosong.
 */
class ArabicNameImportService
{
    public function __construct(private readonly ArabicNameGeneratorContract $generator) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{updated: int, skipped: int, not_found: array<int, string>, invalid: array<int, string>}
     */
    public function importStudents(array $rows, string $schoolId): array
    {
        $updated = 0;
        $skipped = 0;
        $notFound = [];
        $invalid = [];

        foreach ($rows as $row) {
            $arabic = trim((string) ($row['nama_arab'] ?? $row['name_ar'] ?? ''));
            $nis = trim((string) ($row['nis'] ?? ''));
            $nisn = trim((string) ($row['nisn'] ?? ''));

            if ($arabic === '') {
                $skipped++;

                continue;
            }

            if ($nis === '' && $nisn === '') {
                $invalid[] = 'Baris tanpa NIS/NISN';

                continue;
            }

            $student = null;

            if ($nis !== '') {
                $student = Student::query()
                    ->where('school_id', $schoolId)
                    ->where('nis', $nis)
                    ->first();
            }

            if (! $student && $nisn !== '') {
                $student = Student::query()
                    ->where('school_id', $schoolId)
                    ->where('nisn', $nisn)
                    ->first();
            }

            if (! $student) {
                $notFound[] = $nis !== '' ? $nis : $nisn;

                continue;
            }

            $student->update([
                'name_ar' => $arabic,
                'name_ar_status' => 'pending',
                'name_ar_reviewed_at' => null,
                'name_ar_reviewed_by' => null,
            ]);
            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'not_found' => $notFound, 'invalid' => $invalid];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{updated: int, skipped: int, not_found: array<int, string>, invalid: array<int, string>}
     */
    public function importGtk(array $rows, ?string $schoolId): array
    {
        $updated = 0;
        $skipped = 0;
        $notFound = [];
        $invalid = [];

        foreach ($rows as $row) {
            $arabic = trim((string) ($row['nama_arab'] ?? $row['name_ar'] ?? ''));
            $nupy = trim((string) ($row['nupy'] ?? $row['nip'] ?? ''));

            if ($arabic === '') {
                $skipped++;

                continue;
            }

            if ($nupy === '') {
                $invalid[] = 'Baris tanpa NUPY';

                continue;
            }

            $employment = GtkEmployment::query()
                ->where('nupy', $nupy)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->first();

            if (! $employment) {
                $notFound[] = $nupy;

                continue;
            }

            $user = User::find($employment->user_id);

            if (! $user) {
                $notFound[] = $nupy;

                continue;
            }

            $user->update([
                'name_ar' => $arabic,
                'name_ar_status' => 'pending',
                'name_ar_reviewed_at' => null,
                'name_ar_reviewed_by' => null,
            ]);
            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'not_found' => $notFound, 'invalid' => $invalid];
    }

    /**
     * Isi otomatis nama Arab santri (default: hanya yang kosong).
     */
    public function generateStudents(string $schoolId, bool $overwrite = false, ?int $limit = null): int
    {
        $query = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('name');

        if (! $overwrite) {
            $query->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', ''));
        }

        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }

        $count = 0;

        foreach ($query->cursor() as $student) {
            $arabic = $this->generator->generate($student->name);

            if ($arabic !== '') {
                $student->update([
                    'name_ar' => $arabic,
                    'name_ar_status' => 'pending',
                    'name_ar_reviewed_at' => null,
                    'name_ar_reviewed_by' => null,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Isi otomatis nama Arab GTK (default: hanya yang kosong).
     */
    public function generateGtk(?string $schoolId, bool $overwrite = false, ?int $limit = null): int
    {
        $query = User::query()
            ->whereHas('employment')
            ->when($schoolId, fn ($q) => $q->whereIn('id', SchoolGroupService::userIdsForSchool($schoolId)))
            ->orderBy('name');

        if (! $overwrite) {
            $query->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', ''));
        }

        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }

        $count = 0;

        foreach ($query->cursor() as $user) {
            $arabic = $this->generator->generate($user->name);

            if ($arabic !== '') {
                $user->update([
                    'name_ar' => $arabic,
                    'name_ar_status' => 'pending',
                    'name_ar_reviewed_at' => null,
                    'name_ar_reviewed_by' => null,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{total: int, filled: int, missing: int}
     */
    public function studentStats(string $schoolId): array
    {
        $base = Student::query()->where('school_id', $schoolId);
        $total = (clone $base)->count();
        $filled = (clone $base)->whereNotNull('name_ar')->where('name_ar', '!=', '')->count();

        return ['total' => $total, 'filled' => $filled, 'missing' => max(0, $total - $filled)];
    }

    /**
     * @return array{total: int, filled: int, missing: int}
     */
    public function gtkStats(?string $schoolId): array
    {
        $base = User::query()
            ->whereHas('employment')
            ->when($schoolId, fn ($q) => $q->whereIn('id', SchoolGroupService::userIdsForSchool($schoolId)));

        $total = (clone $base)->count();
        $filled = (clone $base)->whereNotNull('name_ar')->where('name_ar', '!=', '')->count();

        return ['total' => $total, 'filled' => $filled, 'missing' => max(0, $total - $filled)];
    }
}
