<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\StudyGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Nonaktifkan rombel yang bukan milik tahun ajaran aktif.
 *
 * Latar belakang: rombel TA lama tetap `is_active = 1` setelah pergantian
 * tahun ajaran, sehingga daftar rombel di banyak halaman tampil ganda
 * (nama sama di dua TA). Rombel lama TIDAK dihapus karena masih menampung
 * penugasan mengajar, buku nilai, dan snapshot analisis.
 *
 * Aturan aman:
 *  - Hanya mengubah kolom `study_groups.is_active`. Tidak ada baris lain
 *    yang disentuh, tidak ada penghapusan.
 *  - Rombel yang masih punya santri aktif (student_class_histories.is_active)
 *    dilewati dan dilaporkan.
 *  - Default dry-run; tulis hanya dengan --commit.
 *  - Portabel MySQL maupun SQLite (memakai Schema, bukan information_schema).
 */
class DeactivateStaleStudyGroups extends Command
{
    protected $signature = 'rombel:deactivate-stale
                            {--commit : Tulis perubahan (default hanya menampilkan laporan)}
                            {--school= : Batasi ke satu sekolah (UUID)}';

    protected $description = 'Nonaktifkan rombel di luar tahun ajaran aktif tanpa menghapus data terkait.';

    /** Tabel yang merujuk study_groups.id — dipakai untuk laporan dampak. */
    private const RELATED_TABLES = [
        'admin_presensi_harian',
        'agenda_attendees',
        'archive_documents',
        'asset_rooms',
        'attendance_recaps',
        'class_schedules',
        'exam_attempts',
        'gtk_employments',
        'gtk_gap_summaries',
        'homeroom_assignments',
        'homeroom_teachers',
        'humas_announcements',
        'jadwal_kbms',
        'lab_activities',
        'lab_schedule_slots',
        'language_daily_logs',
        'language_monthly_activities',
        'language_vocabulary_periods',
        'ljk_exams',
        'ljk_participants',
        'mumtaz_murtafi_candidates',
        'other_teacher_tasks',
        'perangkat_pembelajaran',
        'qr_class_tokens',
        'raport_registrations',
        'student_absences',
        'student_attendances',
        'student_class_histories',
        'study_group_subjects',
        'supervisi',
        'tahfidz_groups',
        'teacher_admin_books',
        'teacher_class_attendances',
        'teachings',
        'teaching_assignments',
        'violation_points',
    ];

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $schoolId = $this->option('school');

        $activeAy = AcademicYear::query()->where('is_active', true)->first();

        if (! $activeAy) {
            $this->error('Tidak ada tahun ajaran aktif. Batalkan.');

            return self::FAILURE;
        }

        $this->line(($commit ? '[COMMIT] ' : '[DRY-RUN] ')
            ."Tahun ajaran aktif: {$activeAy->name} {$activeAy->semester}");

        $groups = StudyGroup::query()
            ->with(['school:id,name', 'academicYear:id,name,semester'])
            ->where('is_active', true)
            ->where(function ($q) use ($activeAy) {
                $q->whereNull('academic_year_id')
                    ->orWhere('academic_year_id', '!=', $activeAy->id);
            })
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('school_id')
            ->orderBy('name')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('Tidak ada rombel di luar TA aktif. Tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        $rows = [];
        $willDeactivate = [];
        $skipped = 0;

        foreach ($groups as $group) {
            $activeStudents = $this->activeStudentCount($group->id);

            if ($activeStudents > 0) {
                $skipped++;
                $rows[] = [
                    $group->name,
                    mb_substr((string) ($group->school?->name ?? '-'), 0, 22),
                    trim(($group->academicYear?->name ?? '?').' '.($group->academicYear?->semester ?? '')),
                    $activeStudents,
                    '—',
                    'DILEWATI: masih ada santri aktif',
                ];

                continue;
            }

            $related = $this->relatedCounts($group->id);
            $total = array_sum($related);
            $top = collect($related)
                ->sortDesc()
                ->take(2)
                ->map(fn ($n, $t) => "{$t}={$n}")
                ->implode(', ');

            $willDeactivate[] = $group->id;

            $rows[] = [
                $group->name,
                mb_substr((string) ($group->school?->name ?? '-'), 0, 22),
                trim(($group->academicYear?->name ?? '?').' '.($group->academicYear?->semester ?? '')),
                0,
                $total,
                $top !== '' ? $top : 'tidak ada data terkait',
            ];
        }

        $this->table(
            ['Rombel', 'Sekolah', 'Tahun Ajaran', 'Santri aktif', 'Baris terkait', 'Rincian / catatan'],
            $rows
        );

        $this->line(sprintf(
            'Akan dinonaktifkan: %d rombel. Dilewati: %d.',
            count($willDeactivate),
            $skipped
        ));

        if (! $commit) {
            $this->newLine();
            $this->warn('DRY-RUN — tidak ada data yang diubah. Jalankan ulang dengan --commit untuk menerapkan.');

            return self::SUCCESS;
        }

        if ($willDeactivate === []) {
            $this->info('Tidak ada rombel yang memenuhi syarat untuk dinonaktifkan.');

            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(function () use ($willDeactivate, &$updated) {
            foreach (array_chunk($willDeactivate, 200) as $chunk) {
                $updated += StudyGroup::query()
                    ->whereIn('id', $chunk)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'updated_at' => now()]);
            }
        });

        Log::info('rombel:deactivate-stale', [
            'updated' => $updated,
            'ids' => $willDeactivate,
        ]);

        $this->info("Selesai. {$updated} rombel dinonaktifkan. Data terkait tidak diubah.");

        return self::SUCCESS;
    }

    private function activeStudentCount(string $studyGroupId): int
    {
        if (! Schema::hasTable('student_class_histories')) {
            return 0;
        }

        return DB::table('student_class_histories')
            ->where('study_group_id', $studyGroupId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function relatedCounts(string $studyGroupId): array
    {
        $counts = [];

        foreach (self::RELATED_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'study_group_id')) {
                continue;
            }

            $count = DB::table($table)->where('study_group_id', $studyGroupId)->count();

            if ($count > 0) {
                $counts[$table] = $count;
            }
        }

        return $counts;
    }
}
