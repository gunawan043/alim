<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\AdminPresensiMapel;
use App\Models\AdminPresensiSiswa;
use App\Models\StudentAttendance;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sinkronisasi kehadiran akademik dari modul lain (UKS, asrama/perizinan).
 *
 * SATU-SATUNYA penulis otomatis untuk presensi harian & presensi mapel dari
 * sumber lintas modul. Prinsip kebijakan Tahap 3:
 *  - Kehadiran kelas milik wali kelas; baris AUTO boleh diperbarui sinkronisasi.
 *  - Baris MANUAL (hasil input wali kelas/guru) TIDAK ditimpa — cukup ditandai
 *    agar diverifikasi ("saling cek kesesuaiannya").
 *  - Status dipetakan: UKS → `sakit`; izin pulang/asrama → `izin`;
 *    kembali → `hadir`.
 */
class AcademicAttendanceSyncService
{
    public const SOURCE_UPS = 'uks';

    public const SOURCE_BOARDING_HEALTH = 'boarding_health';

    public const SOURCE_BOARDING_LEAVE = 'boarding_leave';

    public const SOURCE_RETURN = 'boarding_return';

    /**
     * Tandai siswa tidak hadir untuk rentang tanggal (sakit/izin).
     */
    public function markAsAbsent(
        string $studentId,
        string|int|Carbon $startDate,
        string|int|Carbon $endDate,
        string $source = self::SOURCE_BOARDING_LEAVE,
        ?string $sourceId = null,
        string $status = 'izin',
    ): void {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $context = $this->contextFor($studentId);

        DB::transaction(function () use ($studentId, $start, $end, $source, $sourceId, $status, $context) {
            $current = $start->copy();

            while ($current->lte($end)) {
                $this->writeDaily($studentId, $current, $status, $source, $sourceId, $context);
                $this->writeMapel($studentId, $current, $status, $source, $sourceId, $context);
                $this->writeLegacy($studentId, $current, $status, $source, $context);

                $current->addDay();
            }
        });
    }

    /**
     * Tandai siswa kembali hadir pada tanggal tertentu (pulang/kembali).
     */
    public function markAsPresent(
        string $studentId,
        string|int|Carbon $date,
        string $source = self::SOURCE_RETURN,
        ?string $sourceId = null,
    ): void {
        $day = Carbon::parse($date)->startOfDay();
        $context = $this->contextFor($studentId);

        DB::transaction(function () use ($studentId, $day, $source, $sourceId, $context) {
            $this->writeDaily($studentId, $day, 'hadir', $source, $sourceId, $context);
            $this->writeMapel($studentId, $day, 'hadir', $source, $sourceId, $context);
            $this->writeLegacy($studentId, $day, 'hadir', $source, $context);
        });
    }

    /**
     * @return array{school_id: ?string, academic_year_id: ?string, study_group_id: ?string, semester: string}
     */
    private function contextFor(string $studentId): array
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        $history = StudentClassHistory::query()
            ->where('student_id', $studentId)
            ->when($academicYear?->id, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('is_active', true)
            ->latest()
            ->first();

        return [
            'school_id' => $history?->school_id ?? $history?->studyGroup?->school_id,
            'academic_year_id' => $academicYear?->id ?? $history?->academic_year_id,
            'study_group_id' => $history?->study_group_id,
            'semester' => $academicYear?->semester === 'genap' ? 'genap' : 'ganjil',
        ];
    }

    /**
     * Presensi harian kelas (dikelola wali kelas; baris auto aman dibarui).
     */
    private function writeDaily(string $studentId, Carbon $day, string $status, string $source, ?string $sourceId, array $context): void
    {
        if (! $context['study_group_id'] || ! $context['academic_year_id']) {
            return;
        }

        $existing = AdminPresensiHarian::query()
            ->where('student_id', $studentId)
            ->whereDate('attendance_date', $day->format('Y-m-d'))
            ->first();

        if ($existing && ! $existing->isAutoFilled()) {
            // Catatan manual wali kelas dipertahankan; sinkronisasi hanya
            // menandai bahwa baris perlu dicek ulang kesesuaiannya.
            $existing->forceFill(['verified_at' => null, 'verified_by' => null])->save();

            return;
        }

        $payload = [
            'status' => $status,
            'source' => $source,
            'source_ref' => $sourceId,
            'auto_filled' => true,
            'verified_at' => null,
            'verified_by' => null,
        ];

        if ($existing) {
            $existing->forceFill($payload)->save();

            return;
        }

        AdminPresensiHarian::create($payload + [
            'study_group_id' => $context['study_group_id'],
            'academic_year_id' => $context['academic_year_id'],
            'semester' => $context['semester'],
            'attendance_date' => $day->format('Y-m-d'),
            'student_id' => $studentId,
            'notes' => 'Otomatis dari '.$this->sourceLabel($source),
        ]);
    }

    /**
     * Presensi per jam pelajaran (guru mapel): sesi pada tanggal yang sama.
     */
    private function writeMapel(string $studentId, Carbon $day, string $status, string $source, ?string $sourceId, array $context): void
    {
        if (! $context['study_group_id']) {
            return;
        }

        $bookIds = TeacherAdminBook::query()
            ->where('study_group_id', $context['study_group_id'])
            ->when($context['academic_year_id'], fn ($q) => $q->where('academic_year_id', $context['academic_year_id']))
            ->where('is_active', true)
            ->pluck('id');

        if ($bookIds->isEmpty()) {
            return;
        }

        $sessionIds = AdminPresensiMapel::query()
            ->whereIn('admin_book_id', $bookIds)
            ->whereDate('attendance_date', $day->format('Y-m-d'))
            ->pluck('id');

        foreach ($sessionIds as $sessionId) {
            $row = AdminPresensiSiswa::query()
                ->where('presensi_mapel_id', $sessionId)
                ->where('student_id', $studentId)
                ->first();

            if ($row && ! $row->isAutoFilled()) {
                continue; // catatan manual guru mapel tidak ditimpa
            }

            $payload = [
                'status' => $status,
                'source' => $source,
                'source_ref' => $sourceId,
                'auto_filled' => true,
                'notes' => 'Otomatis dari '.$this->sourceLabel($source),
            ];

            if ($row) {
                $row->forceFill($payload)->save();
            } else {
                AdminPresensiSiswa::create($payload + [
                    'presensi_mapel_id' => $sessionId,
                    'student_id' => $studentId,
                ]);
            }
        }
    }

    /**
     * Tabel legacy `student_attendances` (dibaca Mobile API).
     */
    private function writeLegacy(string $studentId, Carbon $day, string $status, string $source, array $context): void
    {
        // `recorded_by` wajib; sinkronisasi tanpa aktor (mis. queue) melewati
        // tabel legacy — presensi akademik tetap tercatat di tabel utama.
        $recordedBy = auth()->id();

        if (! $recordedBy) {
            return;
        }

        $row = StudentAttendance::query()
            ->where('student_id', $studentId)
            ->whereDate('attendance_date', $day->format('Y-m-d'))
            ->first();

        $payload = [
            'school_id' => $context['school_id'],
            'academic_year_id' => $context['academic_year_id'],
            'study_group_id' => $context['study_group_id'],
            'recorded_by' => $recordedBy,
            'status' => $status,
            'notes' => 'Otomatis dari '.$this->sourceLabel($source),
        ];

        if ($row) {
            $row->forceFill($payload)->save();
        } else {
            StudentAttendance::create($payload + [
                'student_id' => $studentId,
                'attendance_date' => $day->format('Y-m-d'),
            ]);
        }
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            self::SOURCE_UPS => 'UKS',
            self::SOURCE_BOARDING_HEALTH => 'perizinan kesehatan asrama',
            self::SOURCE_BOARDING_LEAVE => 'izin pulang asrama',
            self::SOURCE_RETURN => 'kepulangan/kembali',
            default => $source,
        };
    }
}
