<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\GradeLevelSubject;
use App\Models\Kaldik;
use App\Models\PekanEfektif;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\TeachingAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PekanEfektifService
 *
 * Sumber data utama: Kalender Pendidikan (tabel `kaldik`).
 * Turunan yang dihasilkan:
 *  - Pekan Efektif per semester (minggu efektif/libur/ujian, jumlah hari efektif)
 *  - Alokasi JP efektif per kelas & mapel (fondasi perencanaan pembelajaran guru)
 *
 * Aturan semester:
 *  - Jika `academic_years.semester_ganjil_start/end` & `semester_genap_start/end`
 *    diisi → dipakai sebagai rentang semester.
 *  - Fallback otomatis:
 *      Tahun ajaran mulai Juli  → Ganjil: Jul–Des, Genap: Jan–Jun
 *      Tahun ajaran mulai Jan  → Genap: Jan–Jun, Ganjil: Jul–Des
 */
class PekanEfektifService
{
    public function __construct(private readonly TeachingHoursResolver $hoursResolver) {}

    /**
     * Rentang tanggal satu semester pada tahun ajaran.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function semesterRange(AcademicYear $ay, int $semester): array
    {
        $start = $ay->start_date ? Carbon::parse($ay->start_date)->startOfDay() : now()->startOfYear();
        $end = $ay->end_date ? Carbon::parse($ay->end_date)->startOfDay() : now()->endOfYear();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        // Override eksplisit dari tahun ajaran (bila diisi)
        $overrideStart = $semester === PekanEfektif::SEMESTER_GANJIL
            ? $ay->semester_ganjil_start
            : $ay->semester_genap_start;
        $overrideEnd = $semester === PekanEfektif::SEMESTER_GANJIL
            ? $ay->semester_ganjil_end
            : $ay->semester_genap_end;

        if ($overrideStart && $overrideEnd) {
            $oStart = Carbon::parse($overrideStart)->startOfDay();
            $oEnd = Carbon::parse($overrideEnd)->startOfDay();
            if ($oStart->gt($oEnd)) {
                [$oStart, $oEnd] = [$oEnd, $oStart];
            }

            return [$oStart, $oEnd];
        }

        if ($overrideStart) {
            $start = Carbon::parse($overrideStart)->startOfDay();
        }
        if ($overrideEnd) {
            $end = Carbon::parse($overrideEnd)->startOfDay();
        }

        // Fallback pembagian otomatis
        if ($start->month >= 7) {
            // Tahun ajaran lintas tahun (Jul–Jun)
            if ($semester === PekanEfektif::SEMESTER_GANJIL) {
                $rangeStart = $start->copy();
                $rangeEnd = Carbon::create($start->year, 12, 31)->startOfDay();
            } else {
                $rangeStart = Carbon::create($start->year + 1, 1, 1)->startOfDay();
                $rangeEnd = $end->copy();
            }
        } else {
            // Tahun ajaran dalam satu tahun kalender (Jan–Des)
            if ($semester === PekanEfektif::SEMESTER_GANJIL) {
                $rangeStart = Carbon::create($start->year, 7, 1)->startOfDay();
                $rangeEnd = $end->copy();
            } else {
                $rangeStart = $start->copy();
                $rangeEnd = Carbon::create($start->year, 6, 30)->startOfDay();
            }
        }

        // Clamp ke rentang tahun ajaran
        if ($rangeStart->lt($start)) {
            $rangeStart = $start->copy();
        }
        if ($rangeEnd->gt($end)) {
            $rangeEnd = $end->copy();
        }

        // Guard terakhir: bila hasil tidak valid, bagi dua rentang tahun ajaran
        if ($rangeStart->gt($rangeEnd)) {
            $mid = $start->copy()->addMonthsNoOverflow((int) floor($start->diffInMonths($end) / 2));

            return $semester === PekanEfektif::SEMESTER_GANJIL
                ? [$start->copy(), $mid]
                : [$mid->copy()->addDay(), $end->copy()];
        }

        return [$rangeStart, $rangeEnd];
    }

    /**
     * Event kalender yang berlaku untuk sekolah: kaldik pondok (work_unit null)
     * + agenda satuan kerja sekolah tersebut.
     */
    public function calendarEvents(string $schoolId, string $academicYearId): Collection
    {
        $workUnitId = School::find($schoolId)?->work_unit_id;

        return Kaldik::query()
            ->active()
            ->byAcademicYear($academicYearId)
            ->where(function ($q) use ($workUnitId) {
                $q->whereNull('work_unit_id');
                if ($workUnitId) {
                    $q->orWhere('work_unit_id', $workUnitId);
                }
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Hitung baris pekan efektif dari Kalender Pendidikan (tanpa menyimpan).
     *
     * @return array{rows: array<int, array<string, mixed>>, range: array{0: Carbon, 1: Carbon}, events: Collection, academic_year: AcademicYear}
     */
    public function computeWeeks(string $schoolId, string $academicYearId, int $semester): array
    {
        $ay = AcademicYear::findOrFail($academicYearId);
        [$rangeStart, $rangeEnd] = $this->semesterRange($ay, $semester);
        $events = $this->calendarEvents($schoolId, $academicYearId);

        $liburEvents = $events->filter(fn (Kaldik $e) => in_array($e->type, Kaldik::NON_EFFECTIVE_TYPES, true));
        $ujianEvents = $events->where('type', Kaldik::TYPE_UJIAN);
        $kegiatanEvents = $events->whereIn('type', [
            Kaldik::TYPE_KEGIATAN,
            Kaldik::TYPE_MID_SEMESTER,
            Kaldik::TYPE_TAHUNAN,
        ]);

        $rows = [];
        $mingguKe = 0;
        $cursor = $rangeStart->copy()->startOfWeek(Carbon::MONDAY);

        while ($cursor->lte($rangeEnd)) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->addDays(5); // Senin–Sabtu
            $mingguKe++;

            // Jumlah hari efektif: senin–sabtu, dalam rentang semester, tidak libur.
            $effectiveDays = 0;
            for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
                if ($day->lt($rangeStart) || $day->gt($rangeEnd)) {
                    continue;
                }
                $isLibur = $liburEvents->contains(
                    fn (Kaldik $e) => $day->greaterThanOrEqualTo($e->start_date) && $day->lessThanOrEqualTo($e->end_date)
                );
                if (! $isLibur) {
                    $effectiveDays++;
                }
            }

            // Event yang menimpa pekan ini untuk keterangan & jenis.
            $overlapping = $events->filter(
                fn (Kaldik $e) => $e->start_date->lte($weekEnd) && $e->end_date->gte($weekStart)
            );

            $weekUjian = $ujianEvents->contains(
                fn (Kaldik $e) => $e->start_date->lte($weekEnd) && $e->end_date->gte($weekStart)
            );
            $weekKegiatan = $kegiatanEvents->contains(
                fn (Kaldik $e) => $e->start_date->lte($weekEnd) && $e->end_date->gte($weekStart)
            );

            if ($effectiveDays === 0) {
                $jenis = PekanEfektif::JENIS_LIBUR;
            } elseif ($weekUjian) {
                $jenis = PekanEfektif::JENIS_UJIAN;
            } elseif ($weekKegiatan) {
                $jenis = PekanEfektif::JENIS_KEGIATAN_SEKOLAH;
            } else {
                $jenis = PekanEfektif::JENIS_EFEKTIF;
            }

            $keterangan = $overlapping->pluck('name')->filter()->unique()->implode(', ');

            $rows[] = [
                'minggu_ke' => $mingguKe,
                'tanggal_mulai' => ($weekStart->lt($rangeStart) ? $rangeStart->copy() : $weekStart)->toDateString(),
                'tanggal_selesai' => ($weekEnd->gt($rangeEnd) ? $rangeEnd->copy() : $weekEnd)->toDateString(),
                'jenis' => $jenis,
                'jumlah_hari' => $effectiveDays,
                'keterangan' => $keterangan !== '' ? mb_substr($keterangan, 0, 255) : null,
            ];

            $cursor->addWeek();
        }

        return [
            'rows' => $rows,
            'range' => [$rangeStart, $rangeEnd],
            'events' => $events,
            'academic_year' => $ay,
        ];
    }

    /**
     * Ringkasan pekan efektif dari baris hasil hitung.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public function summarize(array $rows): array
    {
        $totalHariEfektif = 0;
        $totalHariLibur = 0;
        $mingguEfektif = 0;
        $mingguLibur = 0;
        $mingguUjian = 0;

        foreach ($rows as $row) {
            $hari = (int) ($row['jumlah_hari'] ?? 0);
            $totalHariEfektif += $hari;

            if ($hari > 0) {
                $mingguEfektif++;
            } else {
                $mingguLibur++;
            }

            if (($row['jenis'] ?? null) === PekanEfektif::JENIS_UJIAN) {
                $mingguUjian++;
            }

            if (($row['jenis'] ?? null) === PekanEfektif::JENIS_LIBUR) {
                $mulai = Carbon::parse($row['tanggal_mulai']);
                $selesai = Carbon::parse($row['tanggal_selesai']);
                $totalHariLibur += (int) $mulai->diffInDays($selesai) + 1;
            }
        }

        return [
            'total_minggu' => count($rows),
            'minggu_efektif' => $mingguEfektif,
            'minggu_libur' => $mingguLibur,
            'minggu_ujian' => $mingguUjian,
            'total_hari_efektif' => $totalHariEfektif,
            'total_hari_libur' => $totalHariLibur,
        ];
    }

    /**
     * Ringkasan pekan efektif untuk satu sekolah & semester.
     * Bila belum digenerate, dihitung langsung dari Kalender Pendidikan
     * (is_persisted = false) sehingga modul lain tetap mendapat data konsisten.
     *
     * @return array<string, mixed>
     */
    public function summary(string $schoolId, string $academicYearId, int $semester): array
    {
        $rows = PekanEfektif::query()
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->orderBy('minggu_ke')
            ->get();

        if ($rows->isEmpty()) {
            $computed = $this->computeWeeks($schoolId, $academicYearId, $semester);

            return $this->summarize($computed['rows']) + [
                'is_persisted' => false,
                'sumber' => 'Kalender Pendidikan (perhitungan langsung)',
            ];
        }

        $summary = $this->summarize(
            $rows->map(fn (PekanEfektif $p) => [
                'jenis' => $p->jenis,
                'jumlah_hari' => $p->hari_efektif,
                'tanggal_mulai' => $p->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $p->tanggal_selesai?->toDateString(),
            ])->all()
        );

        return $summary + [
            'is_persisted' => true,
            'sumber' => 'Kalender Pendidikan (tersimpan)',
        ];
    }

    /**
     * Generate & simpan Pekan Efektif dari Kalender Pendidikan.
     * Data lama untuk sekolah + tahun ajaran + semester yang sama akan diganti.
     *
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function generate(string $schoolId, string $academicYearId, int $semester, ?string $generatedBy = null): array
    {
        $computed = $this->computeWeeks($schoolId, $academicYearId, $semester);
        $rows = $computed['rows'];

        DB::transaction(function () use ($schoolId, $academicYearId, $semester, $rows, $generatedBy) {
            PekanEfektif::withTrashed()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->forceDelete();

            foreach ($rows as $row) {
                PekanEfektif::create([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'semester' => $semester,
                    'minggu_ke' => $row['minggu_ke'],
                    'tanggal_mulai' => $row['tanggal_mulai'],
                    'tanggal_selesai' => $row['tanggal_selesai'],
                    'jenis' => $row['jenis'],
                    'jumlah_hari' => $row['jumlah_hari'],
                    'keterangan' => $row['keterangan'],
                    'is_generated' => true,
                    'generated_at' => now(),
                ]);
            }
        });

        return [
            'rows' => $rows,
            'summary' => $this->summarize($rows),
            'generated_by' => $generatedBy,
        ];
    }

    /**
     * Alokasi JP efektif per kelas (fondasi perencanaan pembelajaran):
     * JP efektif = JP per minggu × jumlah minggu efektif semester.
     *
     * Mapel diambil dari gabungan tiga sumber resmi tanpa duplikasi:
     * teaching_assignments → study_group_subjects → grade_level_subjects.
     * JP per minggu diselesaikan lewat TeachingHoursResolver (satu aturan).
     *
     * @return array<int, array<string, mixed>>
     */
    public function effectiveJpForStudyGroup(string $schoolId, string $studyGroupId, string $academicYearId, int $semester): array
    {
        $summary = $this->summary($schoolId, $academicYearId, $semester);
        $mingguEfektif = (int) ($summary['minggu_efektif'] ?? 0);

        $studyGroup = StudyGroup::with('gradeLevel')->find($studyGroupId);

        if (! $studyGroup) {
            return [];
        }

        $rows = [];
        $seenSubjects = [];

        // 1) Plotting mengajar (otoritatif) — SK guru.
        $assignments = TeachingAssignment::with(['subject', 'teacher:id,name'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->get();

        foreach ($assignments as $assignment) {
            if (! $assignment->subject) {
                continue;
            }

            $weeklyHours = $this->hoursResolver->resolve(
                $studyGroup,
                $assignment->subject,
                (int) $assignment->weekly_hours
            );

            $seenSubjects[$assignment->subject_id] = true;
            $rows[] = $this->jpRow($assignment->subject, $assignment->teacher?->name, $weeklyHours, $mingguEfektif);
        }

        // 2) Mapel rombel (bila belum ada di plotting).
        $groupSubjects = StudyGroupSubject::with(['subject', 'teacher:id,name'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get();

        foreach ($groupSubjects as $groupSubject) {
            if (! $groupSubject->subject || isset($seenSubjects[$groupSubject->subject_id])) {
                continue;
            }

            $weeklyHours = $this->hoursResolver->resolve(
                $studyGroup,
                $groupSubject->subject,
                (int) $groupSubject->weekly_hours
            );

            $seenSubjects[$groupSubject->subject_id] = true;
            $rows[] = $this->jpRow($groupSubject->subject, $groupSubject->teacher?->name, $weeklyHours, $mingguEfektif);
        }

        // 3) Mapel jenjang (bila belum ada di dua sumber sebelumnya).
        if ($studyGroup->grade_level_id) {
            $gradeSubjects = GradeLevelSubject::with('subject')
                ->where('grade_level_id', $studyGroup->grade_level_id)
                ->where('is_active', true)
                ->get();

            foreach ($gradeSubjects as $gradeSubject) {
                if (! $gradeSubject->subject || isset($seenSubjects[$gradeSubject->subject_id])) {
                    continue;
                }

                $weeklyHours = $this->hoursResolver->resolve($studyGroup, $gradeSubject->subject);
                $seenSubjects[$gradeSubject->subject_id] = true;
                $rows[] = $this->jpRow($gradeSubject->subject, null, $weeklyHours, $mingguEfektif);
            }
        }

        usort($rows, fn ($a, $b) => strcmp($a['subject'], $b['subject']));

        return $rows;
    }

    /**
     * JP efektif untuk satu mapel (tanpa konteks rombel) — dipakai ATP/Kurikulum.
     * JP efektif = JP per minggu (fallback berjenjang) × minggu efektif semester.
     */
    public function effectiveJpForSubject(string $schoolId, string $academicYearId, int $semester, \App\Models\Subject $subject, ?string $gradeLevelId = null): int
    {
        $summary = $this->summary($schoolId, $academicYearId, $semester);
        $mingguEfektif = (int) ($summary['minggu_efektif'] ?? 0);

        $weeklyHours = $this->hoursResolver->resolve(null, $subject, null, $gradeLevelId);

        return $weeklyHours * $mingguEfektif;
    }

    /**
     * @return array<string, mixed>
     */
    private function jpRow(\App\Models\Subject $subject, ?string $teacherName, int $weeklyHours, int $mingguEfektif): array
    {
        return [
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'subject_code' => $subject->code,
            'teacher' => $teacherName ?: '—',
            'weekly_hours' => $weeklyHours,
            'minggu_efektif' => $mingguEfektif,
            'jp_efektif' => $weeklyHours * $mingguEfektif,
        ];
    }
}
