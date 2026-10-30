<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\Alumni;
use App\Models\StudentClassHistory;
use App\Models\StudentLifecycleAudit;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationOut;
use App\Models\StudentTransfer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Laporan mutasi, pertumbuhan, profil, keberlanjutan, dan kenaikan/kelulusan.
 *
 * Prinsip:
 * - Satu santri dihitung satu kali pada satu tahun ajaran.
 * - Mutasi resmi hanya status approved.
 * - Ketidaktersediaan data ditampilkan sebagai belum tercatat, bukan dianggap putus.
 */
class StudentPopulationService
{
    private const MUTATION_IN_TYPES = ['masuk', 'pindahan_masuk'];

    private const MUTATION_OUT_TYPES = ['pindahan_keluar', 'berhenti', 'lulus'];

    // ─────────────────────────────────────────────────────────────
    // 6. MUTASI SANTRI
    // ─────────────────────────────────────────────────────────────

    public function mutations(array $schoolIds, array $filters): array
    {
        $academicYear = $this->academicYear($filters);
        [$from, $to] = $this->dateRange($academicYear, $filters);

        $inMutations = StudentMutationIn::query()
            ->with(['student', 'school'])
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->whereBetween('established_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $outMutations = StudentMutationOut::query()
            ->with(['student', 'school'])
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->whereBetween('established_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $transfers = StudentTransfer::query()
            ->with(['student', 'fromSchool', 'toSchool'])
            ->where('approval_status', 'approved')
            ->whereBetween('transfer_date', [$from->toDateString(), $to->toDateString()])
            ->where(function (Builder $query) use ($schoolIds) {
                $query->whereIn('from_school_id', $schoolIds)
                    ->orWhereIn('to_school_id', $schoolIds);
            })
            ->get();

        $audits = StudentLifecycleAudit::query()
            ->with(['student', 'school'])
            ->whereIn('school_id', $schoolIds)
            ->whereIn('event', ['student.moved_rombel', 'student.promoted'])
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $studentIds = $inMutations->pluck('student_id')
            ->merge($outMutations->pluck('student_id'))
            ->merge($transfers->pluck('student_id'))
            ->merge($audits->pluck('student_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $histories = $this->historiesForStudentIds($studentIds);
        $groupMap = $this->groupMap($histories);

        $rows = collect();
        $seen = [];

        foreach ($inMutations as $mutation) {
            $date = $mutation->established_date ?: $mutation->created_at;
            $type = ($mutation->origin_school_name || $mutation->student_previous_school)
                ? 'pindahan_masuk'
                : 'masuk';
            $context = $this->classContext($histories, $mutation->student_id, $date);

            $rows->push([
                'id' => 'in-'.$mutation->id,
                'date' => $date?->toDateString(),
                'type' => $type,
                'type_label' => $this->mutationLabel($type),
                'student_id' => $mutation->student_id,
                'student_name' => $mutation->student_name ?: $mutation->student?->name,
                'nisn' => $mutation->student_nisn ?: $mutation->student?->nisn,
                'school_id' => $mutation->school_id,
                'school_name' => $mutation->school?->name,
                'grade_name' => $context['grade_name'] ?: ($mutation->accepted_class ?: '-'),
                'class_name' => $context['class_name'] ?: ($mutation->accepted_class ?: '-'),
                'grade_level_id' => $context['grade_level_id'],
                'study_group_id' => $context['study_group_id'],
                'gender' => $mutation->student_gender ?: $mutation->student?->gender,
                'from_name' => $mutation->origin_school_name ?: $mutation->student_previous_school,
                'to_name' => $mutation->school?->name,
                'reason' => $mutation->reason,
                'notes' => $mutation->notes,
                'source' => 'Mutasi masuk',
            ]);

            $seen[$mutation->student_id.'|'.$date?->toDateString().'|in'] = true;
        }

        foreach ($outMutations as $mutation) {
            $date = $mutation->established_date ?: $mutation->created_at;
            $type = match ($mutation->out_type) {
                'dropout' => 'berhenti',
                'graduation' => 'lulus',
                default => 'pindahan_keluar',
            };
            $context = $this->classContext($histories, $mutation->student_id, $date);

            $rows->push([
                'id' => 'out-'.$mutation->id,
                'date' => $date?->toDateString(),
                'type' => $type,
                'type_label' => $this->mutationLabel($type),
                'student_id' => $mutation->student_id,
                'student_name' => $mutation->student_name ?: $mutation->student?->name,
                'nisn' => $mutation->student_nisn ?: $mutation->student?->nisn,
                'school_id' => $mutation->school_id,
                'school_name' => $mutation->school?->name,
                'grade_name' => $context['grade_name'],
                'class_name' => $context['class_name'] ?: ($mutation->student_current_class ?: '-'),
                'grade_level_id' => $context['grade_level_id'],
                'study_group_id' => $context['study_group_id'],
                'gender' => $mutation->student_gender ?: $mutation->student?->gender,
                'from_name' => $mutation->school?->name,
                'to_name' => $mutation->destination_school_name ?: $mutation->graduation_school_name,
                'reason' => $mutation->reason,
                'notes' => $mutation->notes,
                'source' => 'Mutasi keluar',
            ]);

            $seen[$mutation->student_id.'|'.$date?->toDateString().'|out'] = true;
        }

        foreach ($transfers as $transfer) {
            $fromInternal = in_array($transfer->from_school_id, $schoolIds, true);
            $toInternal = in_array($transfer->to_school_id, $schoolIds, true);

            $type = $fromInternal && $toInternal
                ? 'internal_unit'
                : ($toInternal ? 'pindahan_masuk' : 'pindahan_keluar');
            $date = $transfer->transfer_date ?: $transfer->created_at;
            $context = $this->classContext($histories, $transfer->student_id, $date);

            $dedupeKey = $transfer->student_id.'|'.$date?->toDateString().'|'.($toInternal && ! $fromInternal ? 'in' : 'out');
            $internalKey = $transfer->student_id.'|'.$date?->toDateString().'|internal';
            if (isset($seen[$dedupeKey]) || ($type === 'internal_unit' && isset($seen[$internalKey]))) {
                continue;
            }

            if ($type === 'internal_unit') {
                $seen[$internalKey] = true;
            }

            $rows->push([
                'id' => 'transfer-'.$transfer->id,
                'date' => $date?->toDateString(),
                'type' => $type,
                'type_label' => $this->mutationLabel($type),
                'student_id' => $transfer->student_id,
                'student_name' => $transfer->student?->name,
                'nisn' => $transfer->student?->nisn,
                'school_id' => $toInternal ? $transfer->to_school_id : $transfer->from_school_id,
                'school_name' => $toInternal ? $transfer->toSchool?->name : $transfer->fromSchool?->name,
                'grade_name' => $context['grade_name'],
                'class_name' => $context['class_name'],
                'grade_level_id' => $context['grade_level_id'],
                'study_group_id' => $context['study_group_id'],
                'gender' => $transfer->student?->gender,
                'from_name' => $transfer->fromSchool?->name,
                'to_name' => $transfer->toSchool?->name,
                'reason' => $transfer->reason,
                'notes' => $transfer->notes,
                'source' => 'Data pindahan',
            ]);
        }

        foreach ($audits as $audit) {
            $payload = $audit->payload ?: [];
            $date = $audit->occurred_at;
            $internalKey = $audit->student_id.'|'.$date?->toDateString().'|internal';
            if (isset($seen[$internalKey])) {
                continue;
            }
            $seen[$internalKey] = true;

            $fromGroup = $groupMap->get($payload['from_sg'] ?? null);
            $toGroup = $groupMap->get($payload['to_sg'] ?? null);
            $fromSchoolId = $fromGroup?->school_id ?? $audit->school_id;
            $toSchoolId = $toGroup?->school_id ?? $audit->school_id;
            $fromLevel = $fromGroup?->gradeLevel?->level;
            $toLevel = $toGroup?->gradeLevel?->level;

            $type = 'class_move';
            if ($fromSchoolId !== $toSchoolId) {
                $type = 'internal_unit';
            } elseif ($fromLevel !== null && $toLevel !== null && $fromLevel !== $toLevel) {
                $type = 'internal_grade';
            }

            $rows->push([
                'id' => 'audit-'.$audit->id,
                'date' => $date?->toDateString(),
                'type' => $type,
                'type_label' => $this->mutationLabel($type),
                'student_id' => $audit->student_id,
                'student_name' => $audit->student?->name,
                'nisn' => $audit->student?->nisn,
                'school_id' => $toSchoolId,
                'school_name' => $audit->school?->name,
                'grade_name' => $toGroup?->gradeLevel?->name ?? '-',
                'class_name' => $toGroup?->name ?? '-',
                'grade_level_id' => $toGroup?->grade_level_id,
                'study_group_id' => $toGroup?->id,
                'gender' => $audit->student?->gender,
                'from_name' => $fromGroup?->name ?? '-',
                'to_name' => $toGroup?->name ?? '-',
                'reason' => $audit->event === 'student.promoted' ? 'Kenaikan/perpindahan jenjang' : 'Perpindahan kelas',
                'notes' => null,
                'source' => 'Riwayat kelas',
            ]);
        }

        $rows = $rows->filter(function (array $row) use ($filters) {
            if ($row['date'] === null) {
                return false;
            }
            if (! empty($filters['grade_level_id']) && $row['grade_level_id'] !== $filters['grade_level_id']) {
                return false;
            }
            if (! empty($filters['study_group_id']) && $row['study_group_id'] !== $filters['study_group_id']) {
                return false;
            }
            if (! empty($filters['gender']) && $row['gender'] !== $filters['gender']) {
                return false;
            }
            if (! empty($filters['q'])) {
                $term = mb_strtolower(trim((string) $filters['q']));
                $haystack = mb_strtolower(trim(($row['student_name'] ?? '').' '.($row['nisn'] ?? '')));

                return str_contains($haystack, $term);
            }

            return true;
        })
            ->sortByDesc(fn (array $row) => $row['date'].' '.$row['id'])
            ->values();

        $summary = [
            'total' => $rows->count(),
            'masuk' => $rows->where('type', 'masuk')->count(),
            'pindahan_masuk' => $rows->where('type', 'pindahan_masuk')->count(),
            'pindahan_keluar' => $rows->where('type', 'pindahan_keluar')->count(),
            'berhenti' => $rows->where('type', 'berhenti')->count(),
            'lulus' => $rows->where('type', 'lulus')->count(),
            'internal_unit' => $rows->where('type', 'internal_unit')->count(),
            'internal_grade' => $rows->where('type', 'internal_grade')->count(),
            'class_move' => $rows->where('type', 'class_move')->count(),
        ];
        $summary['total_in'] = $summary['masuk'] + $summary['pindahan_masuk'];
        $summary['total_out'] = $summary['pindahan_keluar'] + $summary['berhenti'] + $summary['lulus'];
        $summary['total_internal'] = $summary['internal_unit'] + $summary['internal_grade'] + $summary['class_move'];
        $summary['net'] = $summary['total_in'] - $summary['total_out'];

        $monthly = $this->mutationSeries($rows, $from, $to);
        $yearly = $this->mutationYearlySeries($rows, $from, $to);
        $byUnit = $this->mutationGroupSeries($rows, 'school_name');
        $byGrade = $this->mutationGroupSeries($rows, 'grade_name');
        $byClass = $this->mutationGroupSeries($rows, 'class_name');

        $findings = collect();
        $dropoutReasons = $rows->where('type', 'berhenti')
            ->filter(fn (array $row) => filled($row['reason']))
            ->groupBy(fn (array $row) => trim((string) $row['reason']))
            ->map(fn (Collection $group, string $reason) => [
                'title' => $reason,
                'count' => $group->count(),
                'description' => 'Alasan berhenti yang tercatat.',
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        foreach ($dropoutReasons as $finding) {
            $findings->push($finding);
        }

        $missingDestination = $rows->whereIn('type', ['pindahan_keluar', 'lulus'])
            ->filter(fn (array $row) => blank($row['to_name']))
            ->count();

        if ($missingDestination > 0) {
            $findings->push([
                'title' => 'Sekolah tujuan belum diisi',
                'count' => $missingDestination,
                'description' => 'Data keluar tanpa sekolah tujuan menyulitkan pelacakan lanjutan.',
            ]);
        }

        return [
            'academic_year' => $academicYear,
            'period' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'row_count' => $rows->count(),
            'summary' => $summary,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'by_unit' => $byUnit,
            'by_grade' => $byGrade,
            'by_class' => $byClass,
            'findings' => $findings,
            'source_note' => 'Mutasi resmi (disetujui) dan perpindahan internal yang tercatat pada audit siklus santri.',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 7. PERTUMBUHAN POPULASI
    // ─────────────────────────────────────────────────────────────

    public function growth(array $schoolIds, array $filters): array
    {
        $academicYear = $this->academicYear($filters);
        $years = $this->annualYears();
        $histories = $this->allHistories($schoolIds, $filters);
        $historiesByYear = $histories->groupBy(fn (StudentClassHistory $history) => $history->academicYear?->name ?? $history->academic_year_id);

        $inMutations = StudentMutationIn::query()
            ->with('student:id,name,school_id')
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->get()
            ->filter(fn (StudentMutationIn $mutation) => $mutation->established_date !== null);

        $outMutations = StudentMutationOut::query()
            ->with('student:id,name,school_id')
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->get()
            ->filter(fn (StudentMutationOut $mutation) => $mutation->established_date !== null);

        $alumni = Alumni::query()
            ->whereIn('school_id', $schoolIds)
            ->get();

        $firstYearByStudent = $histories->groupBy('student_id')->map(
            fn (Collection $items) => $items->sortBy(fn (StudentClassHistory $history) => $history->academicYear?->start_date?->timestamp ?? 0)->first()?->academicYear?->name
        );

        $inByYear = $inMutations->groupBy(fn (StudentMutationIn $mutation) => $this->yearIdForDate($years, $mutation->established_date));
        $outByYear = $outMutations->groupBy(fn (StudentMutationOut $mutation) => $this->yearIdForDate($years, $mutation->established_date));
        $alumniByYear = $alumni->groupBy(fn (Alumni $alumnus) => $this->yearIdForDate($years, $alumnus->graduation_date ?: Carbon::create((int) $alumnus->graduation_year, 12, 31)));

        $metrics = [];
        $previousYear = null;

        foreach ($years as $year) {
            $currentHistories = $historiesByYear->get($year->id, collect());
            $current = $currentHistories->unique('student_id')->values();
            $currentIds = $current->pluck('student_id')->unique()->values();

            $in = $inByYear->get($year->id, collect())->pluck('student_id')->filter()->unique();
            $out = $outByYear->get($year->id, collect())->where('out_type', 'mutation')->pluck('student_id')->filter()->unique();
            $dropouts = $outByYear->get($year->id, collect())->where('out_type', 'dropout')->pluck('student_id')->filter()->unique();
            $graduates = $outByYear->get($year->id, collect())->where('out_type', 'graduation')->pluck('student_id')->filter()->unique()
                ->merge($alumniByYear->get($year->id, collect())->pluck('student_id'))
                ->merge(
                    $current->filter(fn (StudentClassHistory $history) => $history->student?->status === 'graduate'
                        && $history->student?->graduation_date
                        && $history->student->graduation_date->between($year->start_date, $year->end_date))
                        ->pluck('student_id')
                )
                ->unique();

            $new = $currentIds->filter(function (string $studentId) use ($current, $firstYearByStudent, $year, $in, $previousYear) {
                if (($firstYearByStudent[$studentId] ?? null) !== $year->id || $in->contains($studentId)) {
                    return false;
                }

                if (! $previousYear) {
                    $history = $current->firstWhere('student_id', $studentId);

                    return $history?->student?->entry_date
                        && $history->student->entry_date->gt($year->start_date);
                }

                return true;
            });

            if ($previousYear) {
                $openingCount = (int) ($metrics[$previousYear->id]['closing'] ?? 0);
            } else {
                $openingCount = $current->filter(fn (StudentClassHistory $history) => ! $history->student?->entry_date
                    || ! $history->student->entry_date->gt($year->start_date))
                    ->pluck('student_id')
                    ->unique()
                    ->count();
            }

            $exitedIds = $out->merge($dropouts)->merge($graduates)->unique();
            $closing = $openingCount + $new->count() + $in->count() - $out->count() - $dropouts->count() - $graduates->count();
            $closingDirect = $currentIds->diff($exitedIds)->count();
            $net = $closing - $openingCount;

            $metrics[$year->id] = [
                'id' => $year->id,
                'name' => $year->name,
                'start_date' => $year->start_date?->toDateString(),
                'end_date' => $year->end_date?->toDateString(),
                'opening' => $openingCount,
                'new' => $new->count(),
                'mutation_in' => $in->count(),
                'mutation_out' => $out->count(),
                'dropout' => $dropouts->count(),
                'graduates' => $graduates->count(),
                'closing' => $closing,
                'closing_direct' => $closingDirect,
                'net_growth' => $net,
                'growth_rate' => $openingCount > 0 ? round(($net / $openingCount) * 100, 2) : null,
                'reconciliation_difference' => $closing - $closingDirect,
                'current_population' => $currentIds->count(),
                'new_student_ids' => $new->values()->all(),
                'mutation_in_ids' => $in->values()->all(),
                'mutation_out_ids' => $out->values()->all(),
                'dropout_ids' => $dropouts->values()->all(),
                'graduate_ids' => $graduates->values()->all(),
            ];

            $previousYear = $year;
        }

        $selected = $metrics[$academicYear->name] ?? [
            'opening' => 0, 'new' => 0, 'mutation_in' => 0, 'mutation_out' => 0,
            'dropout' => 0, 'graduates' => 0, 'closing' => 0, 'closing_direct' => 0,
            'net_growth' => 0, 'growth_rate' => null, 'reconciliation_difference' => 0,
            'current_population' => 0,
        ];

        [$startDate] = $this->yearBounds($academicYear);
        $previousYear = $years->first(fn ($year) => $year->start_date && $startDate && $year->start_date->lt($startDate));
        $previousHistories = $previousYear ? $historiesByYear->get($previousYear->id, collect()) : collect();
        $currentHistories = $historiesByYear->get($academicYear->name, collect());

        $byUnit = $this->growthBreakdown($previousHistories, $currentHistories, 'school_id', 'school_name');
        $byGrade = $this->growthBreakdown($previousHistories, $currentHistories, 'grade_level_id', 'grade_name');
        $byLevel = $this->growthBreakdown($previousHistories, $currentHistories, 'school_level', 'school_level');

        $findings = collect();
        if (($selected['reconciliation_difference'] ?? 0) !== 0) {
            $findings->push([
                'title' => 'Selisih rekonsiliasi populasi',
                'count' => abs((int) $selected['reconciliation_difference']),
                'description' => 'Angka rumus pertumbuhan berbeda dengan populasi terdaftar. Periksa riwayat kelas dan mutasi yang belum lengkap.',
            ]);
        }

        if (($selected['mutation_in'] ?? 0) > 0 && ($selected['new'] ?? 0) > 0) {
            $overlap = collect($selected['new_student_ids'] ?? [])->intersect($selected['mutation_in_ids'] ?? [])->count();
            if ($overlap > 0) {
                $findings->push([
                    'title' => 'Santri baru dan mutasi masuk tumpang tindih',
                    'count' => $overlap,
                    'description' => 'Rekonsiliasi telah menghapus irisan agar santri tidak dihitung dua kali.',
                ]);
            }
        }

        return [
            'academic_year' => $academicYear,
            'previous_year' => $previousYear,
            'summary' => $selected,
            'series' => array_values($metrics),
            'breakdown' => [
                'unit' => $byUnit,
                'grade' => $byGrade,
                'level' => $byLevel,
            ],
            'findings' => $findings,
            'source_note' => 'Populasi dihitung dari santri unik pada riwayat kelas; mutasi resmi hanya status disetujui.',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 8. PROFIL DAN SEBARAN
    // ─────────────────────────────────────────────────────────────

    public function profile(array $schoolIds, array $filters): array
    {
        $academicYear = $this->academicYear($filters);
        $currentHistories = $this->histories($schoolIds, $academicYear->id, $filters)
            ->unique('student_id')
            ->values();

        $annualYears = $this->annualYears();
        $yearIndex = $annualYears->search(fn ($year) => $year->name === $academicYear->name);
        $previousYear = $yearIndex !== false && $yearIndex > 0 ? $annualYears->get($yearIndex - 1) : null;
        $previousHistories = $previousYear
            ? $this->histories($schoolIds, $previousYear->ids[0], $filters)->unique('student_id')->values()
            : collect();

        $students = $currentHistories->pluck('student')->filter()->unique('id')->values();
        $ageGroups = $this->ageGroups($students, $academicYear);
        $total = $currentHistories->count();
        $male = $students->where('gender', 'L')->count();
        $female = $students->where('gender', 'P')->count();
        $classes = $currentHistories->pluck('study_group_id')->filter()->unique();
        $rombelCount = $classes->count();

        $byUnit = $this->profileBreakdown($currentHistories, 'school_id', 'school_name');
        $byGrade = $this->profileBreakdown($currentHistories, 'grade_level_id', 'grade_name');
        $byClass = $this->classBreakdown($currentHistories);

        $previousByUnit = $this->profileBreakdown($previousHistories, 'school_id', 'school_name')->keyBy('id');
        $composition = $byUnit->map(function (array $row) use ($previousByUnit) {
            $previous = $previousByUnit->get($row['id']);
            $previousCount = (int) ($previous['total'] ?? 0);

            return array_merge($row, [
                'previous' => $previousCount,
                'delta' => $row['total'] - $previousCount,
                'delta_rate' => $previousCount > 0 ? round((($row['total'] - $previousCount) / $previousCount) * 100, 2) : null,
            ]);
        })->values();

        $priorTotal = $previousHistories->count();
        $growthRate = $priorTotal > 0 ? round((($total - $priorTotal) / $priorTotal) * 100, 2) : null;

        return [
            'academic_year' => $academicYear,
            'previous_year' => $previousYear,
            'summary' => [
                'total' => $total,
                'male' => $male,
                'female' => $female,
                'unknown_gender' => max(0, $total - $male - $female),
                'rombel' => $rombelCount,
                'average_per_rombel' => $rombelCount > 0 ? round($total / $rombelCount, 2) : null,
                'previous_total' => $priorTotal,
                'growth_rate' => $growthRate,
            ],
            'age_groups' => $ageGroups,
            'by_unit' => $byUnit,
            'by_grade' => $byGrade,
            'by_class' => $byClass,
            'composition' => $composition,
            'source_note' => 'Populasi aktif dihitung dari santri unik yang memiliki riwayat kelas pada tahun ajaran terpilih.',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 9. KEBERLANJUTAN DAN RETENSI
    // ─────────────────────────────────────────────────────────────

    public function continuity(array $schoolIds, array $filters): array
    {
        $academicYear = $this->academicYear($filters);
        $annualYears = $this->annualYears();
        $yearIndex = $annualYears->search(fn ($year) => $year->name === $academicYear->name);
        $nextYear = $yearIndex !== false ? $annualYears->get($yearIndex + 1) : null;
        $previousYear = $yearIndex !== false && $yearIndex > 0 ? $annualYears->get($yearIndex - 1) : null;

        $currentHistories = $this->histories($schoolIds, $academicYear->id, $filters)->unique('student_id')->values();
        $currentIds = $currentHistories->pluck('student_id')->filter()->unique()->values();
        $studentIds = $currentIds->all();
        $allHistories = $this->historiesForStudentIds($studentIds, $schoolIds);
        $allByStudent = $allHistories->groupBy('student_id');
        $firstByStudent = $allByStudent->map(fn (Collection $rows) => $rows->sortBy(fn (StudentClassHistory $h) => $h->academicYear?->start_date?->timestamp ?? 0)->first());
        $lastByStudent = $allByStudent->map(fn (Collection $rows) => $rows->sortByDesc(fn (StudentClassHistory $h) => $h->academicYear?->start_date?->timestamp ?? 0)->first());

        $outIds = StudentMutationOut::query()
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('out_type');

        $continuedIds = collect();
        foreach ($currentIds as $studentId) {
            $last = $lastByStudent->get($studentId);
            if ($last && $last->academicYear?->name !== $academicYear->name) {
                $continuedIds->push($studentId);
            }
        }

        $journey = $currentHistories->map(function (StudentClassHistory $history) use ($firstByStudent, $lastByStudent, $outIds) {
            $student = $history->student;
            $first = $firstByStudent->get($history->student_id);
            $last = $lastByStudent->get($history->student_id);
            $graduation = $outIds->get('graduation', collect())->firstWhere('student_id', $history->student_id);

            return [
                'student_id' => $history->student_id,
                'student_name' => $student?->name,
                'nisn' => $student?->nisn,
                'first_year' => $first?->academicYear?->name,
                'first_class' => $first?->studyGroup?->name,
                'last_year' => $last?->academicYear?->name,
                'last_class' => $last?->studyGroup?->name,
                'continued' => $last && $last->academicYear?->name !== $history->academicYear?->name,
                'status' => $student?->status_text,
                'graduation_year' => $graduation?->graduation_year ?? $student?->graduation_year,
                'graduation_date' => $graduation?->established_date?->toDateString() ?? $student?->graduation_date?->toDateString(),
            ];
        })->values();

        $notContinued = $journey->where('continued', false)->values();
        $graduated = $notContinued->filter(fn (array $row) => $row['graduation_year'] !== null)->count();
        $cohorts = $this->continuityCohorts($schoolIds, $filters, $academicYear);

        return [
            'academic_year' => $academicYear,
            'next_year' => $nextYear,
            'previous_year' => $previousYear,
            'summary' => [
                'cohort' => $currentHistories->count(),
                'continued' => $continuedIds->count(),
                'not_continued' => max(0, $currentHistories->count() - $continuedIds->count()),
                'graduated' => $graduated,
                'known_exit' => $outIds->get('mutation', collect())->count() + $outIds->get('dropout', collect())->count(),
                'not_recorded' => max(0, $notContinued->count() - $graduated),
                'rate' => $nextYear && ! $nextYear->start_date?->isFuture() && $currentHistories->count() > 0
                    ? round(($continuedIds->count() / $currentHistories->count()) * 100, 2)
                    : null,
            ],
            'journey' => $journey,
            'cohorts' => $cohorts,
            'source_note' => 'Keberlanjutan dihitung dari riwayat kelas setelah tahun acuan. Tidak melanjutkan di unit internal tidak otomatis berarti putus sekolah.',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 10. KENAIKAN KELAS DAN KELULUSAN
    // ─────────────────────────────────────────────────────────────

    public function promotions(array $schoolIds, array $filters): array
    {
        $academicYear = $this->academicYear($filters);
        $annualYears = $this->annualYears();
        $yearIndex = $annualYears->search(fn ($year) => $year->name === $academicYear->name);
        $nextYear = $yearIndex !== false ? $annualYears->get($yearIndex + 1) : null;

        $currentHistories = $this->histories($schoolIds, $academicYear->id, $filters)
            ->unique('student_id')
            ->values();
        $studentIds = $currentHistories->pluck('student_id')->all();
        $nextHistories = $nextYear
            ? StudentClassHistory::query()
                ->with(['studyGroup.gradeLevel', 'studyGroup.school'])
                ->whereIn('academic_year_id', $nextYear->ids)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id')
            : collect();

        [$yearStart, $yearEnd] = $this->yearBounds($academicYear);
        $yearStart ??= now()->startOfYear();
        $yearEnd ??= now()->endOfYear();

        $graduationMutations = StudentMutationOut::query()
            ->with('student')
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->where('out_type', 'graduation')
            ->whereBetween('established_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->get();

        $movedAudits = StudentLifecycleAudit::query()
            ->with('student')
            ->whereIn('school_id', $schoolIds)
            ->where('event', 'student.moved_rombel')
            ->whereBetween('occurred_at', [$yearStart, $yearEnd])
            ->get();

        $details = $currentHistories->map(function (StudentClassHistory $history) use ($nextHistories, $graduationMutations, $yearStart, $yearEnd) {
            $student = $history->student;
            $next = $nextHistories->get($history->student_id);
            $currentLevel = $history->studyGroup?->gradeLevel?->level;
            $nextLevel = $next?->studyGroup?->gradeLevel?->level;
            $graduationMutation = $graduationMutations->firstWhere('student_id', $history->student_id);
            $graduated = (bool) $graduationMutation
                || ($student?->status === 'graduate' && $student?->graduation_date
                    && $student->graduation_date->between($yearStart, $yearEnd));

            if ($next) {
                if ($next->studyGroup?->school_id !== $history->studyGroup?->school_id) {
                    $action = 'moved_unit';
                } elseif ($currentLevel !== null && $nextLevel !== null && $nextLevel > $currentLevel) {
                    $action = 'promoted';
                } elseif ($currentLevel !== null && $nextLevel !== null && $nextLevel < $currentLevel) {
                    $action = 'moved_down';
                } else {
                    $action = 'retained';
                }
            } elseif ($graduated) {
                $action = 'graduated';
            } else {
                $action = 'not_recorded';
            }

            return [
                'student_id' => $history->student_id,
                'student_name' => $student?->name,
                'nisn' => $student?->nisn,
                'school_id' => $history->studyGroup?->school_id,
                'school_name' => $history->studyGroup?->school?->name,
                'grade_name' => $history->studyGroup?->gradeLevel?->name,
                'class_name' => $history->studyGroup?->name,
                'next_school_name' => $next?->studyGroup?->school?->name,
                'next_grade_name' => $next?->studyGroup?->gradeLevel?->name,
                'next_class_name' => $next?->studyGroup?->name,
                'action' => $action,
                'action_label' => $this->promotionLabel($action),
                'rombel_moved' => $next && $next->study_group_id !== $history->study_group_id && $nextLevel === $currentLevel,
                'graduation_year' => $graduationMutation?->graduation_year ?? $student?->graduation_year,
            ];
        })->values();

        $summary = [
            'total' => $details->count(),
            'promoted' => $details->where('action', 'promoted')->count(),
            'retained' => $details->where('action', 'retained')->count(),
            'moved_unit' => $details->where('action', 'moved_unit')->count(),
            'moved_down' => $details->where('action', 'moved_down')->count(),
            'graduated' => $details->where('action', 'graduated')->count(),
            'not_recorded' => $details->where('action', 'not_recorded')->count(),
            'rombel_moved' => $movedAudits->count(),
            'promotion_rate' => null,
            'retention_rate' => null,
        ];

        $summary['promotion_rate'] = $summary['total'] > 0 ? round((($summary['promoted'] + $summary['moved_unit']) / $summary['total']) * 100, 2) : null;
        $summary['retention_rate'] = $summary['total'] > 0 ? round((($summary['promoted'] + $summary['retained'] + $summary['moved_unit']) / $summary['total']) * 100, 2) : null;

        $byUnit = $this->promotionBreakdown($details, 'school_name');
        $byGrade = $this->promotionBreakdown($details, 'grade_name');
        $graduation = $this->graduationSeries($schoolIds, $filters);
        $selectedGraduation = $graduation->firstWhere('id', $academicYear->name) ?? [
            'graduates' => 0, 'final_cohort' => 0, 'rate' => null, 'duration_average' => null,
        ];

        $cohorts = $this->graduationCohorts($schoolIds, $filters);
        $graduationByUnit = $details->where('action', 'graduated')
            ->groupBy(fn (array $row) => $row['school_name'] ?: 'Tidak diketahui')
            ->map(fn (Collection $group, string $name) => ['name' => $name, 'graduates' => $group->count()])
            ->sortByDesc('graduates')
            ->values();
        $graduationByGrade = $details->where('action', 'graduated')
            ->groupBy(fn (array $row) => $row['grade_name'] ?: 'Tidak diketahui')
            ->map(fn (Collection $group, string $name) => ['name' => $name, 'graduates' => $group->count()])
            ->sortByDesc('graduates')
            ->values();

        return [
            'academic_year' => $academicYear,
            'next_year' => $nextYear,
            'summary' => $summary,
            'details' => $details,
            'by_unit' => $byUnit,
            'by_grade' => $byGrade,
            'graduation' => $selectedGraduation,
            'graduation_series' => $graduation,
            'graduation_by_unit' => $graduationByUnit,
            'graduation_by_grade' => $graduationByGrade,
            'cohorts' => $cohorts,
            'source_note' => 'Naik/tinggal kelas dibandingkan dengan riwayat kelas pada tahun ajaran berikutnya. Kelulusan memakai data kelulusan resmi/alumni yang terdeduplikasi.',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function academicYear(array $filters): AcademicYear
    {
        return AcademicYear::findOrFail($filters['academic_year_id']);
    }

    /**
     * Tahun ajaran dapat tersimpan sebagai dua baris semester. Semua laporan
     * populasi memakai nama tahun ajaran agar tidak menghitung satu tahun dua kali.
     */
    private function yearIds(AcademicYear $academicYear): array
    {
        return AcademicYear::query()
            ->where('name', $academicYear->name)
            ->pluck('id')
            ->all();
    }

    private function yearBounds(AcademicYear $academicYear): array
    {
        $rows = AcademicYear::query()->where('name', $academicYear->name)->get();

        return [
            $rows->min(fn (AcademicYear $row) => $row->start_date?->timestamp) ? Carbon::createFromTimestamp($rows->min(fn (AcademicYear $row) => $row->start_date?->timestamp))->startOfDay() : null,
            $rows->max(fn (AcademicYear $row) => $row->end_date?->timestamp) ? Carbon::createFromTimestamp($rows->max(fn (AcademicYear $row) => $row->end_date?->timestamp))->endOfDay() : null,
        ];
    }

    private function annualYears(): Collection
    {
        return AcademicYear::query()
            ->orderBy('start_date')
            ->get()
            ->groupBy('name')
            ->map(function (Collection $rows, string $name) {
                $rows = $rows->sortBy('start_date')->values();

                return (object) [
                    'id' => $name,
                    'name' => $name,
                    'ids' => $rows->pluck('id')->all(),
                    'start_date' => $rows->first()?->start_date,
                    'end_date' => $rows->last()?->end_date,
                ];
            })
            ->values();
    }

    private function dateRange(AcademicYear $academicYear, array $filters): array
    {
        [$defaultFrom, $defaultTo] = $this->yearBounds($academicYear);
        $from = ! empty($filters['dari'])
            ? Carbon::parse($filters['dari'])->startOfDay()
            : ($defaultFrom ?? now()->startOfYear());
        $to = ! empty($filters['sampai'])
            ? Carbon::parse($filters['sampai'])->endOfDay()
            : ($defaultTo ?? now()->endOfYear());

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function histories(array $schoolIds, string $academicYearId, array $filters): Collection
    {
        $academicYear = AcademicYear::find($academicYearId);

        return $this->historyQuery($schoolIds, $filters)
            ->whereIn('academic_year_id', $academicYear ? $this->yearIds($academicYear) : [$academicYearId])
            ->get();
    }

    private function allHistories(array $schoolIds, array $filters): Collection
    {
        return $this->historyQuery($schoolIds, $filters)->get();
    }

    private function historyQuery(array $schoolIds, array $filters): Builder
    {
        return StudentClassHistory::query()
            ->with(['student', 'studyGroup.school', 'studyGroup.gradeLevel', 'academicYear'])
            ->whereHas('studyGroup', function (Builder $query) use ($schoolIds, $filters) {
                $query->whereIn('school_id', $schoolIds);
                if (! empty($filters['grade_level_id'])) {
                    $query->where('grade_level_id', $filters['grade_level_id']);
                }
                if (! empty($filters['study_group_id'])) {
                    $query->where('id', $filters['study_group_id']);
                }
            })
            ->when(! empty($filters['gender']), fn (Builder $query) => $query->whereHas('student', fn (Builder $student) => $student->where('gender', $filters['gender'])))
            ->when(! empty($filters['q']), fn (Builder $query) => $query->whereHas('student', function (Builder $student) use ($filters) {
                $term = '%'.trim((string) $filters['q']).'%';
                $student->where(fn (Builder $sub) => $sub->where('name', 'like', $term)->orWhere('nisn', 'like', $term)->orWhere('nis', 'like', $term));
            }));
    }

    private function historiesForStudentIds(array $studentIds, array $schoolIds = []): Collection
    {
        if (empty($studentIds)) {
            return collect();
        }

        return StudentClassHistory::query()
            ->with(['student', 'studyGroup.school', 'studyGroup.gradeLevel', 'academicYear'])
            ->whereIn('student_id', $studentIds)
            ->when(! empty($schoolIds), fn (Builder $query) => $query->whereHas('studyGroup', fn (Builder $group) => $group->whereIn('school_id', $schoolIds)))
            ->get()
            ->groupBy('student_id')
            ->flatMap(fn (Collection $rows) => $rows->sortBy(fn (StudentClassHistory $history) => $history->academicYear?->start_date?->timestamp ?? 0));
    }

    private function groupMap(Collection $histories): Collection
    {
        return $histories->pluck('studyGroup')->filter()->unique('id')->keyBy('id');
    }

    private function classContext(Collection $histories, ?string $studentId, ?Carbon $date): array
    {
        if (! $studentId) {
            return ['grade_name' => '-', 'class_name' => '-', 'grade_level_id' => null, 'study_group_id' => null];
        }

        $rows = $histories->where('student_id', $studentId)->values();
        if ($rows->isEmpty()) {
            return ['grade_name' => '-', 'class_name' => '-', 'grade_level_id' => null, 'study_group_id' => null];
        }

        $match = null;
        if ($date) {
            $match = $rows->first(fn (StudentClassHistory $history) => $history->join_date && $history->join_date->lte($date)
                && (! $history->leave_date || $history->leave_date->gte($date)));
        }
        $match ??= $rows->filter(fn (StudentClassHistory $history) => $history->join_date && $history->join_date->lte($date ?? now()))
            ->sortByDesc(fn (StudentClassHistory $history) => $history->join_date?->timestamp ?? 0)
            ->first();
        $match ??= $rows->sortByDesc(fn (StudentClassHistory $history) => $history->academicYear?->start_date?->timestamp ?? 0)->first();

        return [
            'grade_name' => $match->studyGroup?->gradeLevel?->name ?? '-',
            'class_name' => $match->studyGroup?->name ?? '-',
            'grade_level_id' => $match->studyGroup?->grade_level_id,
            'study_group_id' => $match->study_group_id,
        ];
    }

    private function mutationLabel(string $type): string
    {
        return match ($type) {
            'masuk' => 'Masuk Baru',
            'pindahan_masuk' => 'Pindahan Masuk',
            'pindahan_keluar' => 'Pindahan Keluar',
            'berhenti' => 'Berhenti / Drop Out',
            'lulus' => 'Lulus',
            'internal_unit' => 'Pindah Unit Internal',
            'internal_grade' => 'Pindah Jenjang Internal',
            'class_move' => 'Pindah Rombel',
            default => ucfirst($type),
        };
    }

    private function mutationSeries(Collection $rows, Carbon $from, Carbon $to): array
    {
        $series = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $in = $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && in_array($row['type'], self::MUTATION_IN_TYPES, true))->count();
            $out = $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && in_array($row['type'], self::MUTATION_OUT_TYPES, true))->count();
            $internal = $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && str_starts_with($row['type'], 'internal_'))->count()
                + $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && $row['type'] === 'class_move')->count();

            $series[] = ['key' => $key, 'label' => $cursor->format('M Y'), 'in' => $in, 'out' => $out, 'internal' => $internal];
            $cursor->addMonth();
        }

        return $series;
    }

    private function mutationYearlySeries(Collection $rows, Carbon $from, Carbon $to): array
    {
        $series = [];
        for ($year = (int) $from->format('Y'); $year <= (int) $to->format('Y'); $year++) {
            $key = (string) $year;
            $series[] = [
                'key' => $key,
                'label' => $key,
                'in' => $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && in_array($row['type'], self::MUTATION_IN_TYPES, true))->count(),
                'out' => $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && in_array($row['type'], self::MUTATION_OUT_TYPES, true))->count(),
                'internal' => $rows->filter(fn (array $row) => ($row['date'] ?? null) && str_starts_with($row['date'], $key) && (str_starts_with($row['type'], 'internal_') || $row['type'] === 'class_move'))->count(),
            ];
        }

        return $series;
    }

    private function mutationGroupSeries(Collection $rows, string $key): Collection
    {
        return $rows->groupBy(fn (array $row) => $row[$key] ?: 'Tidak diketahui')
            ->map(function (Collection $group, string $name) {
                return [
                    'name' => $name,
                    'in' => $group->filter(fn (array $row) => in_array($row['type'], self::MUTATION_IN_TYPES, true))->count(),
                    'out' => $group->filter(fn (array $row) => in_array($row['type'], self::MUTATION_OUT_TYPES, true))->count(),
                    'internal' => $group->filter(fn (array $row) => str_starts_with($row['type'], 'internal_') || $row['type'] === 'class_move')->count(),
                    'total' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function yearIdForDate(Collection $years, $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $year = $years->first(fn ($candidate) => $candidate->start_date && $candidate->end_date
            && $date->between($candidate->start_date, $candidate->end_date));

        return $year?->id;
    }

    private function growthBreakdown(Collection $previous, Collection $current, string $idKey, string $nameKey): Collection
    {
        $previousGroups = $previous->groupBy(fn (StudentClassHistory $history) => $this->historyGroupKey($history, $idKey));
        $currentGroups = $current->groupBy(fn (StudentClassHistory $history) => $this->historyGroupKey($history, $idKey));
        $keys = $previousGroups->keys()->merge($currentGroups->keys())->unique();

        return $keys->map(function (string $key) use ($previousGroups, $currentGroups, $nameKey) {
            $previousRows = $previousGroups->get($key, collect());
            $currentRows = $currentGroups->get($key, collect());
            $sample = $previousRows->first() ?? $currentRows->first();
            $opening = $previousRows->pluck('student_id')->unique()->count();
            $closing = $currentRows->pluck('student_id')->unique()->count();

            return [
                'id' => $key,
                'name' => $this->historyGroupName($sample, $nameKey),
                'opening' => $opening,
                'closing' => $closing,
                'net' => $closing - $opening,
                'rate' => $opening > 0 ? round((($closing - $opening) / $opening) * 100, 2) : null,
            ];
        })->sortByDesc('closing')->values();
    }

    private function historyGroupKey(StudentClassHistory $history, string $key): string
    {
        return match ($key) {
            'school_id' => (string) ($history->studyGroup?->school_id ?: 'unknown'),
            'grade_level_id' => (string) ($history->studyGroup?->grade_level_id ?: 'unknown'),
            'school_level' => (string) ($history->studyGroup?->school?->school_level ?: 'unknown'),
            default => (string) ($history->studyGroup?->{$key} ?: 'unknown'),
        };
    }

    private function historyGroupName(?StudentClassHistory $history, string $key): string
    {
        if (! $history) {
            return 'Tidak diketahui';
        }

        return match ($key) {
            'school_name' => $history->studyGroup?->school?->name ?? 'Tidak diketahui',
            'grade_name' => $history->studyGroup?->gradeLevel?->name ?? 'Tidak diketahui',
            'school_level' => $history->studyGroup?->school?->level_text ?? $history->studyGroup?->school?->school_level ?? 'Tidak diketahui',
            default => 'Tidak diketahui',
        };
    }

    private function ageGroups(Collection $students, AcademicYear $academicYear): array
    {
        $reference = $academicYear->end_date ?? now();
        $bands = [
            'Di bawah 6' => [null, 5],
            '6–7' => [6, 7],
            '8–9' => [8, 9],
            '10–11' => [10, 11],
            '12–13' => [12, 13],
            '14–15' => [14, 15],
            '16–17' => [16, 17],
            '18+' => [18, null],
            'Tidak diketahui' => null,
        ];
        $counts = array_fill_keys(array_keys($bands), 0);

        foreach ($students as $student) {
            if (! $student->birth_date) {
                $counts['Tidak diketahui']++;

                continue;
            }
            $age = (int) $student->birth_date->diffInYears($reference);
            $matched = false;
            foreach ($bands as $label => $range) {
                if ($range === null || $label === 'Tidak diketahui') {
                    continue;
                }
                [$min, $max] = $range;
                if (($min === null || $age >= $min) && ($max === null || $age <= $max)) {
                    $counts[$label]++;
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                $counts['18+']++;
            }
        }

        return collect($counts)->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])->values()->all();
    }

    private function profileBreakdown(Collection $histories, string $idKey, string $nameKey): Collection
    {
        return $histories->groupBy(fn (StudentClassHistory $history) => $this->historyGroupKey($history, $idKey))
            ->map(function (Collection $group, string $key) use ($nameKey) {
                $sample = $group->first();
                $students = $group->pluck('student')->filter()->unique('id');

                return [
                    'id' => $key,
                    'name' => $this->historyGroupName($sample, $nameKey),
                    'total' => $group->pluck('student_id')->unique()->count(),
                    'male' => $students->where('gender', 'L')->count(),
                    'female' => $students->where('gender', 'P')->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function classBreakdown(Collection $histories): Collection
    {
        return $histories->groupBy('study_group_id')->map(function (Collection $group, string $id) {
            $sample = $group->first();
            $total = $group->pluck('student_id')->unique()->count();
            $capacity = (int) ($sample->studyGroup?->capacity ?? 0);

            return [
                'id' => $id,
                'name' => $sample->studyGroup?->name ?? 'Tidak diketahui',
                'school_name' => $sample->studyGroup?->school?->name ?? '-',
                'grade_name' => $sample->studyGroup?->gradeLevel?->name ?? '-',
                'total' => $total,
                'capacity' => $capacity,
                'fill_rate' => $capacity > 0 ? round(($total / $capacity) * 100, 2) : null,
            ];
        })->sortByDesc('total')->values();
    }

    private function continuityCohorts(array $schoolIds, array $filters, AcademicYear $selected): Collection
    {
        $histories = $this->allHistories($schoolIds, $filters);
        $byYear = $histories->groupBy(fn (StudentClassHistory $history) => $history->academicYear?->name ?? $history->academic_year_id);
        $years = $this->annualYears();
        $cohorts = collect();

        foreach ($years as $index => $year) {
            $current = $byYear->get($year->id, collect())->unique('student_id');
            if ($current->isEmpty()) {
                continue;
            }
            $nextYear = $years->get($index + 1);
            $nextIds = $nextYear ? $byYear->get($nextYear->id, collect())->pluck('student_id')->unique() : collect();
            $currentIds = $current->pluck('student_id')->unique();
            $continued = $currentIds->intersect($nextIds)->count();

            $cohorts->push([
                'id' => $year->id,
                'name' => $year->name,
                'cohort' => $currentIds->count(),
                'continued' => $continued,
                'not_continued' => max(0, $currentIds->count() - $continued),
                'rate' => $nextYear && $currentIds->count() > 0 ? round(($continued / $currentIds->count()) * 100, 2) : null,
                'selected' => $year->id === $selected->name,
            ]);
        }

        return $cohorts->values();
    }

    private function promotionLabel(string $action): string
    {
        return match ($action) {
            'promoted' => 'Naik Kelas',
            'retained' => 'Tinggal Kelas',
            'moved_unit' => 'Pindah Unit',
            'moved_down' => 'Pindah ke Jenjang Lebih Rendah',
            'graduated' => 'Lulus',
            'not_recorded' => 'Belum Tercatat',
            default => ucfirst($action),
        };
    }

    private function promotionBreakdown(Collection $details, string $key): Collection
    {
        return $details->groupBy(fn (array $row) => $row[$key] ?: 'Tidak diketahui')
            ->map(function (Collection $group, string $name) {
                return [
                    'name' => $name,
                    'total' => $group->count(),
                    'promoted' => $group->where('action', 'promoted')->count(),
                    'retained' => $group->where('action', 'retained')->count(),
                    'moved_unit' => $group->where('action', 'moved_unit')->count(),
                    'moved_down' => $group->where('action', 'moved_down')->count(),
                    'graduated' => $group->where('action', 'graduated')->count(),
                    'not_recorded' => $group->where('action', 'not_recorded')->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function graduationSeries(array $schoolIds, array $filters): Collection
    {
        $years = $this->annualYears();
        $histories = $this->allHistories($schoolIds, $filters);
        $byYear = $histories->groupBy(fn (StudentClassHistory $history) => $history->academicYear?->name ?? $history->academic_year_id);
        $alumni = Alumni::query()->whereIn('school_id', $schoolIds)->get();
        $out = StudentMutationOut::query()
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->where('out_type', 'graduation')
            ->get();

        return $years->map(function ($year) use ($byYear, $histories, $alumni, $out) {
            $current = $byYear->get($year->id, collect())->unique('student_id');
            $maxLevel = $current->max(fn (StudentClassHistory $history) => $history->studyGroup?->gradeLevel?->level ?? 0);
            $finalCohort = $current->filter(fn (StudentClassHistory $history) => ($history->studyGroup?->gradeLevel?->level ?? 0) === $maxLevel)
                ->pluck('student_id')->unique();
            $graduates = $out->filter(fn (StudentMutationOut $mutation) => $mutation->established_date
                && $mutation->established_date->between($year->start_date, $year->end_date))
                ->pluck('student_id')
                ->merge($alumni->where('graduation_year', (int) substr($year->name, 0, 4))->pluck('student_id'))
                ->unique();
            $durations = $graduates->map(function (string $studentId) use ($histories, $out, $alumni) {
                $rows = $histories->where('student_id', $studentId)->sortBy(fn (StudentClassHistory $history) => $history->academicYear?->start_date?->timestamp ?? 0);
                $first = $rows->first();
                $graduation = $out->firstWhere('student_id', $studentId)?->established_date
                    ?? $alumni->firstWhere('student_id', $studentId)?->graduation_date;
                if (! $first || ! $graduation || ! $first->academicYear?->start_date) {
                    return null;
                }

                return max(0, round($first->academicYear->start_date->diffInYears($graduation, false), 2));
            })->filter(fn ($value) => $value !== null);

            return [
                'id' => $year->id,
                'name' => $year->name,
                'graduates' => $graduates->count(),
                'final_cohort' => $finalCohort->count(),
                'rate' => $finalCohort->count() > 0 ? round(($graduates->count() / $finalCohort->count()) * 100, 2) : null,
                'duration_average' => $durations->count() > 0 ? round($durations->avg(), 2) : null,
            ];
        })->values();
    }

    private function graduationCohorts(array $schoolIds, array $filters): Collection
    {
        $histories = $this->allHistories($schoolIds, $filters);
        $alumni = Alumni::query()->whereIn('school_id', $schoolIds)->get();
        $out = StudentMutationOut::query()
            ->whereIn('school_id', $schoolIds)
            ->where('status', 'approved')
            ->where('out_type', 'graduation')
            ->get();

        $rows = collect();
        foreach ($alumni as $alumnus) {
            $studentHistories = $histories->where('student_id', $alumnus->student_id)->sortBy(fn (StudentClassHistory $history) => $history->academicYear?->start_date?->timestamp ?? 0);
            $first = $studentHistories->first();
            $last = $studentHistories->last();
            $graduationDate = $alumnus->graduation_date;
            $rows->push([
                'student_id' => $alumnus->student_id,
                'student_name' => $alumnus->student?->name,
                'entry_year' => $first?->academicYear?->name,
                'last_year' => $last?->academicYear?->name,
                'graduation_year' => $alumnus->graduation_year,
                'duration' => $first?->academicYear?->start_date && $graduationDate
                    ? max(0, round($first->academicYear->start_date->diffInYears($graduationDate, false), 2))
                    : null,
            ]);
        }

        return $rows->groupBy('entry_year')->map(function (Collection $group, string $entryYear) {
            return [
                'entry_year' => $entryYear,
                'graduates' => $group->count(),
                'duration_average' => $group->pluck('duration')->filter(fn ($value) => $value !== null)->avg(),
            ];
        })->sortKeys()->values();
    }
}
