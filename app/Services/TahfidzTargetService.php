<?php

namespace App\Services;

use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzMuqorrorPlan;
use App\Models\TahfidzStudentTarget;
use Illuminate\Support\Facades\DB;

/**
 * Distribusi target muqorror per jenjang dan arah juz santri.
 *
 * Alumni (asal sekolah sama dengan sekolah saat ini) bergerak dari juz 1
 * menuju 30. Non-alumni bergerak dari juz 30 menuju 1.
 */
class TahfidzTargetService
{
    /**
     * Muqorror yang dipakai sebuah halaqah.
     *
     * Jenjang diambil dari satuan pendidikan (Sekolah Dasar → muqorror SD,
     * Sekolah Menengah Pertama → SMP, dan seterusnya). Sebelumnya jenjang
     * dibaca dari rombel halaqah, padahal halaqah sengaja tidak terikat
     * rombel — akibatnya jenjang selalu kosong dan halaqah SD memakai
     * muqorror SMA yang kebetulan urutan pertama.
     */
    public function planForGroup(TahfidzGroup $group): ?TahfidzMuqorrorPlan
    {
        $group->loadMissing('gradeLevel', 'studyGroup.gradeLevel', 'school', 'academicYear');
        $gradeId = $group->grade_level_id ?: $group->studyGroup?->grade_level_id;
        $jenjang = $this->jenjangForGroup($group);
        $semester = $group->academicYear?->semester ?: 'ganjil';

        $plans = TahfidzMuqorrorPlan::with('months')
            ->where('is_active', true)
            ->where('academic_year_id', $group->academic_year_id)
            ->where('semester', $semester)
            ->where(function ($query) use ($group) {
                $query->whereNull('school_id')
                    ->orWhere('school_id', $group->school_id);
            })
            ->get();

        // Muqorror dengan jenjang berbeda TIDAK boleh dipakai: target SD
        // tidak sama dengan SMA. Bila tidak ada yang cocok, hasilnya null
        // sehingga pemanggil memberi pesan, bukan diam-diam memakai jenjang lain.
        $plans = $plans->filter(fn (TahfidzMuqorrorPlan $plan) => $plan->jenjang === null
            || $jenjang === null
            || strtolower((string) $plan->jenjang) === $jenjang);

        return $plans
            ->sortByDesc(function (TahfidzMuqorrorPlan $plan) use ($gradeId, $jenjang, $group) {
                $score = 0;
                if ($gradeId && (string) $plan->grade_level_id === (string) $gradeId) {
                    $score += 10;
                }
                if ($jenjang && strtolower((string) $plan->jenjang) === $jenjang) {
                    $score += 5;
                }
                if ((string) $plan->school_id === (string) $group->school_id) {
                    $score += 2;
                }

                return $score;
            })
            ->first();
    }

    /**
     * Jenjang halaqah: sd/smp/sma. Diambil dari jenjang satuan pendidikan,
     * bukan dari rombel.
     */
    public function jenjangForGroup(TahfidzGroup $group): ?string
    {
        $level = strtolower((string) ($group->school?->school_level ?? ''));

        if (str_starts_with($level, 'sd')) {
            return 'sd';
        }
        if (str_starts_with($level, 'smp')) {
            return 'smp';
        }
        if (str_starts_with($level, 'sma') || str_starts_with($level, 'smk') || str_starts_with($level, 'ma')) {
            return 'sma';
        }

        return null;
    }

    public function applyToGroup(TahfidzGroup $group, ?string $userId = null): int
    {
        $plan = $this->planForGroup($group);
        if (! $plan || $plan->months->isEmpty()) {
            return 0;
        }

        $group->loadMissing('members.student');
        $members = $group->members
            ->where('status', 'aktif')
            ->filter(fn ($member) => $member->student !== null);

        $written = 0;

        DB::transaction(function () use ($group, $plan, $members, $userId, &$written) {
            foreach ($members as $member) {
                $student = $member->student;
                $direction = $this->directionFor($student, $group->school_id);
                $nextJuz = $this->nextJuzFor($student, $group);

                foreach ($plan->months as $month) {
                    $juzStart = $month->target_juz_start ?: $nextJuz;
                    $juzEnd = $month->target_juz_end ?: $juzStart;

                    $material = $direction === 'asc'
                        ? $month->alumni_material
                        : $month->non_alumni_material;

                    $notes = collect([
                        'Target muqorror: '.$plan->title,
                        'Arah juz: '.($direction === 'asc' ? '1 → 30 (alumni)' : '30 → 1 (non-alumni)'),
                        $material ? 'Materi '.($direction === 'asc' ? 'alumni' : 'non-alumni').': '.$material : null,
                        $month->notes,
                    ])->filter()->implode("\n");

                    TahfidzStudentTarget::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'tahfidz_group_id' => $group->id,
                            'academic_year_id' => $group->academic_year_id,
                            'semester' => $plan->semester,
                            'target_bulan' => $month->month,
                        ],
                        [
                            'juz_start' => $juzStart,
                            'juz_end' => $juzEnd,
                            'target_halaman' => $month->target_pages,
                            'target_hadits' => 0,
                            'assigned_by' => $userId ?: $group->teacher_id ?: $plan->created_by,
                            'notes' => $notes,
                        ]
                    );

                    $written++;
                }
            }
        });

        return $written;
    }

    /**
     * Alumni = santri yang sekolah asalnya sama dengan satuan pendidikan
     * tempat ia sekarang belajar. Alumni mulai dari juz 1 (depan),
     * non-alumni mulai dari juz 30 (belakang).
     */
    public function isAlumni(Student $student, ?string $schoolId): bool
    {
        return $student->origin_school_id !== null
            && $schoolId !== null
            && (string) $student->origin_school_id === (string) $schoolId;
    }

    public function directionFor(Student $student, ?string $schoolId): string
    {
        return $this->isAlumni($student, $schoolId) ? 'asc' : 'desc';
    }

    public function nextJuzFor(Student $student, TahfidzGroup $group): int
    {
        $completed = TahfidzJuzProgress::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $group->academic_year_id)
            ->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])
            ->pluck('juz_number')
            ->map(fn ($juz) => (int) $juz)
            ->all();

        $sequence = $this->directionFor($student, $group->school_id) === 'asc'
            ? range(1, 30)
            : range(30, 1);

        foreach ($sequence as $juz) {
            if (! in_array($juz, $completed, true)) {
                return $juz;
            }
        }

        return $sequence[0];
    }
}
