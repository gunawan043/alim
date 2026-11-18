<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\GradeLevelSubject;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyeragamkan seluruh data akademik aktif ke satu periode.
 *
 * Tujuan: tahun ajaran rombel, mapel, buku nilai, KKTP, dan dokumen
 * kurikulum memakai TA aktif yang sama (default 2026/2027 ganjil).
 *
 * Prinsip aman:
 *  - Hanya menyentuh data AKTIF pada TA bernama sama (mis. 2026/2027 genap
 *    → 2026/2027 ganjil). Arsip tahun ajaran lain tidak diubah.
 *  - Idempoten: aman dijalankan berulang.
 *  - Rombel di TA lain hanya dipindah bila masih memiliki santri aktif dan
 *    belum ada rombel bernama sama pada TA target.
 *  - Buku nilai dibuat dari struktur mapel rombel (study_group_subjects).
 *    Guru diambil berurutan: guru SGS → penugasan mengajar → wali kelas →
 *    kepala satuan pendidikan.
 */
class SyncActiveAcademicYear extends Command
{
    protected $signature = 'academic:sync-active
                            {--year=2026/2027 : Nama tahun ajaran target}
                            {--semester=ganjil : Semester target (ganjil|genap)}
                            {--school= : Batasi provisioning ke satu sekolah (UUID)}
                            {--dry-run : Hitung dan tampilkan tanpa menulis}
                            {--force : Lewati konfirmasi (untuk deploy otomatis)}';

    protected $description = 'Seragamkan rombel, mapel, buku nilai, dan dokumen kurikulum ke tahun ajaran aktif.';

    /** @var array<string, int> */
    private array $stats = [
        'groups' => 0,
        'sgs_created' => 0,
        'books_created' => 0,
        'books_updated' => 0,
        'books_skipped' => 0,
        'groups_moved' => 0,
        'curriculum_rows' => 0,
        'period_rows' => 0,
    ];

    public function handle(): int
    {
        $yearName = (string) $this->option('year');
        $semester = in_array($this->option('semester'), ['ganjil', 'genap'], true)
            ? (string) $this->option('semester')
            : 'ganjil';
        $dryRun = (bool) $this->option('dry-run');

        $target = AcademicYear::query()
            ->where('name', $yearName)
            ->where('semester', $semester)
            ->first();

        if (! $target) {
            $this->error("Tahun ajaran {$yearName} semester {$semester} tidak ditemukan.");

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Target periode: {$target->name} {$target->semester} ({$target->id})");

        if (! $dryRun && ! $target->is_active) {
            DB::transaction(function () use ($target) {
                AcademicYear::query()->where('is_active', true)->update(['is_active' => false]);
                $target->forceFill(['is_active' => true])->save();
            });

            $this->line('  ✓ Tahun ajaran target diaktifkan.');
        }

        $this->syncStrayGroups($target, $dryRun);
        $this->syncCurriculumPeriod($target, $dryRun);
        $this->provisionBooks($target, $dryRun);

        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Rombel diproses', $this->stats['groups']],
                ['Rombel dipindah ke TA target', $this->stats['groups_moved']],
                ['Assignment mapel dibuat', $this->stats['sgs_created']],
                ['Buku nilai dibuat', $this->stats['books_created']],
                ['Buku nilai diperbarui', $this->stats['books_updated']],
                ['Buku nilai dilewati (tanpa guru)', $this->stats['books_skipped']],
                ['Baris kurikulum diselaraskan', $this->stats['curriculum_rows']],
                ['Baris transaksi periode diselaraskan', $this->stats['period_rows']],
            ],
        );

        $this->info($dryRun ? 'Dry-run selesai — tidak ada perubahan ditulis.' : 'Sinkronisasi periode selesai.');

        return self::SUCCESS;
    }

    /**
     * Rombel aktif di TA lain yang masih punya santri aktif dipindah ke target,
     * hanya jika belum ada rombel dengan nama sama pada TA target.
     */
    private function syncStrayGroups(AcademicYear $target, bool $dryRun): void
    {
        $groups = StudyGroup::query()
            ->with('school')
            ->where('academic_year_id', '!=', $target->id)
            ->where('is_active', true)
            ->when($this->option('school'), fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->whereIn('id', StudentClassHistory::query()
                ->where('is_active', true)
                ->select('study_group_id'))
            ->get();

        foreach ($groups as $group) {
            $duplicate = StudyGroup::query()
                ->where('school_id', $group->school_id)
                ->where('academic_year_id', $target->id)
                ->where('name', $group->name)
                ->exists();

            if ($duplicate) {
                $this->warn("  ! {$group->school?->name} {$group->name}: sudah ada rombel di TA target, dibiarkan.");

                continue;
            }

            $this->line("  → Pindah rombel {$group->school?->name} {$group->name} ke TA target.");

            if ($dryRun) {
                $this->stats['groups_moved']++;

                continue;
            }

            DB::transaction(function () use ($group, $target) {
                $bookIds = TeacherAdminBook::query()
                    ->where('study_group_id', $group->id)
                    ->pluck('id')
                    ->all();

                $group->forceFill(['academic_year_id' => $target->id])->save();

                StudentClassHistory::query()
                    ->where('study_group_id', $group->id)
                    ->where('is_active', true)
                    ->update(['academic_year_id' => $target->id]);

                StudyGroupSubject::query()
                    ->where('study_group_id', $group->id)
                    ->update(['academic_year_id' => $target->id]);

                TeachingAssignment::query()
                    ->where('study_group_id', $group->id)
                    ->where('status', 'active')
                    ->update(['academic_year_id' => $target->id]);

                TeacherAdminBook::query()
                    ->where('study_group_id', $group->id)
                    ->update([
                        'academic_year_id' => $target->id,
                        'semester' => $target->semester,
                    ]);

                if ($bookIds !== []) {
                    foreach (['admin_nilai_sumatif', 'admin_nilai_formatif', 'pembiasaan_pagi', 'admin_penghargaan_akademik'] as $table) {
                        if (Schema::hasTable($table) && Schema::hasColumn($table, 'admin_book_id')) {
                            DB::table($table)->whereIn('admin_book_id', $bookIds)->update(['semester' => $target->semester]);
                        }
                    }
                }
            });

            $this->stats['groups_moved']++;
        }
    }

    /**
     * Selaraskan dokumen kurikulum pada TA bernama sama (mis. genap → ganjil)
     * dan transaksi yang masih menempel pada semester lain di tahun yang sama.
     */
    private function syncCurriculumPeriod(AcademicYear $target, bool $dryRun): void
    {
        $otherIds = AcademicYear::query()
            ->where('name', $target->name)
            ->where('id', '!=', $target->id)
            ->pluck('id')
            ->all();

        $curriculumTables = [
            'capaian_pembelajaran',
            'tujuan_pembelajaran',
            'alur_tujuan_pembelajaran',
            'perangkat_pembelajaran',
            'prota',
            'prosem',
            'pekan_efektif',
            'school_curriculum_settings',
            'kurikulum_edit_grants',
            'subject_kktp',
            'grade_level_subjects',
            'rapor_distribution_settings',
        ];

        $periodTables = [
            'admin_catatan_guru',
            'admin_jurnal_pembelajaran',
            'admin_nilai_formatif',
            'admin_nilai_sumatif',
            'admin_penghargaan_akademik',
            'admin_presensi_guru',
            'admin_presensi_harian',
            'admin_presensi_mapel',
            'pembiasaan_pagi',
            'raport_registrations',
            'student_absences',
            'todos',
        ];

        if ($otherIds !== []) {
            foreach ($curriculumTables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'academic_year_id')) {
                    continue;
                }

                $count = DB::table($table)->whereIn('academic_year_id', $otherIds)->count();
                if ($count === 0) {
                    continue;
                }

                if (! $dryRun) {
                    DB::table($table)->whereIn('academic_year_id', $otherIds)->update([
                        'academic_year_id' => $target->id,
                    ] + (Schema::hasColumn($table, 'semester') ? ['semester' => $target->semester] : []));
                }

                $this->stats['curriculum_rows'] += $count;
                $this->line("  ✓ {$table}: {$count} baris diselaraskan.");
            }

            foreach ($periodTables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'academic_year_id')) {
                    continue;
                }

                $count = DB::table($table)->whereIn('academic_year_id', $otherIds)->count();
                if ($count === 0) {
                    continue;
                }

                if (! $dryRun) {
                    DB::table($table)->whereIn('academic_year_id', $otherIds)->update([
                        'academic_year_id' => $target->id,
                    ] + (Schema::hasColumn($table, 'semester') ? ['semester' => $target->semester] : []));
                }

                $this->stats['period_rows'] += $count;
                $this->line("  ✓ {$table}: {$count} baris transaksi diselaraskan.");
            }
        }
    }

    /**
     * Pastikan setiap rombel aktif pada TA target punya struktur mapel dan
     * buku nilai untuk semester target.
     */
    private function provisionBooks(AcademicYear $target, bool $dryRun): void
    {
        $groups = StudyGroup::query()
            ->with(['school', 'gradeLevel'])
            ->where('academic_year_id', $target->id)
            ->where('is_active', true)
            ->when($this->option('school'), fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->orderBy('school_id')
            ->orderBy('name')
            ->get();

        foreach ($groups as $group) {
            $this->stats['groups']++;

            $subjects = StudyGroupSubject::query()
                ->with('subject')
                ->where('study_group_id', $group->id)
                ->where('is_active', true)
                ->get();

            if ($subjects->isEmpty()) {
                $this->stats['sgs_created'] += $this->copySubjectsFromGradeLevel($group, $target, $dryRun);

                $subjects = StudyGroupSubject::query()
                    ->with('subject')
                    ->where('study_group_id', $group->id)
                    ->where('is_active', true)
                    ->get();
            }

            if ($subjects->isEmpty()) {
                $this->warn("  ! {$group->school?->name} {$group->name}: tidak ada struktur mapel, dilewati.");

                continue;
            }

            $fallbackTeacherId = $group->homeroom_teacher_id
                ?: $group->school?->principal_user_id;

            foreach ($subjects as $subject) {
                $teacherId = $subject->teacher_id
                    ?: TeachingAssignment::query()
                        ->where('school_id', $group->school_id)
                        ->where('study_group_id', $group->id)
                        ->where('subject_id', $subject->subject_id)
                        ->where('academic_year_id', $target->id)
                        ->where('status', 'active')
                        ->value('teacher_id')
                    ?: $fallbackTeacherId;

                if (! $teacherId) {
                    $this->stats['books_skipped']++;

                    continue;
                }

                $kktpId = SubjectKktp::query()
                    ->where('school_id', $group->school_id)
                    ->where('subject_id', $subject->subject_id)
                    ->where('grade_level_id', $group->grade_level_id)
                    ->where('academic_year_id', $target->id)
                    ->where('semester', $target->semester)
                    ->value('id');

                $book = TeacherAdminBook::query()
                    ->where('subject_id', $subject->subject_id)
                    ->where('study_group_id', $group->id)
                    ->where('academic_year_id', $target->id)
                    ->where('semester', $target->semester)
                    ->first();

                if ($book) {
                    $changes = [];

                    if (! $book->teacher_id && $teacherId) {
                        $changes['teacher_id'] = $teacherId;
                    }
                    if ((string) $book->school_id !== (string) $group->school_id) {
                        $changes['school_id'] = $group->school_id;
                    }
                    if (! $book->kktp_id && $kktpId) {
                        $changes['kktp_id'] = $kktpId;
                    }
                    if (! $book->is_active) {
                        $changes['is_active'] = true;
                    }

                    if ($changes !== []) {
                        if (! $dryRun) {
                            $book->forceFill($changes)->save();
                        }

                        $this->stats['books_updated']++;
                    }

                    continue;
                }

                if (! $dryRun) {
                    TeacherAdminBook::create([
                        'teacher_id' => $teacherId,
                        'subject_id' => $subject->subject_id,
                        'study_group_id' => $group->id,
                        'school_id' => $group->school_id,
                        'academic_year_id' => $target->id,
                        'semester' => $target->semester,
                        'kktp_id' => $kktpId,
                        'is_active' => true,
                    ]);
                }

                $this->stats['books_created']++;
            }
        }
    }

    /**
     * Salin struktur mapel dari grade_level_subjects bila rombel belum punya.
     */
    private function copySubjectsFromGradeLevel(StudyGroup $group, AcademicYear $target, bool $dryRun): int
    {
        if (! $group->grade_level_id) {
            return 0;
        }

        $rows = GradeLevelSubject::query()
            ->where('grade_level_id', $group->grade_level_id)
            ->where('academic_year_id', $target->id)
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($rows as $row) {
            $exists = StudyGroupSubject::query()
                ->where('study_group_id', $group->id)
                ->where('subject_id', $row->subject_id)
                ->exists();

            if ($exists) {
                continue;
            }

            if (! $dryRun) {
                StudyGroupSubject::create([
                    'school_id' => $group->school_id,
                    'academic_year_id' => $target->id,
                    'study_group_id' => $group->id,
                    'subject_id' => $row->subject_id,
                    'weekly_hours' => (float) ($row->allocation_hours ?: 2),
                    'is_active' => true,
                ]);
            }

            $created++;
        }

        return $created;
    }
}
