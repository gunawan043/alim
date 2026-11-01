<?php

namespace App\Services\Dormitory;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\Dormitory;
use App\Models\DormitoryActivityAttendance;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryPermit;
use App\Models\DormitoryPointAutomation;
use App\Models\DormitoryResident;
use App\Models\DormitoryViolation;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Menghubungkan event kehadiran/kegiatan/izin ke poin pelanggaran asrama.
 *
 * Semua pelanggaran otomatis menyimpan source_type + source_id sehingga:
 *  - tidak dobel saat event disimpan berulang,
 *  - dapat dibatalkan otomatis saat status berubah (mis. alpa → hadir),
 *  - tidak menimpa catatan pelanggaran manual.
 */
class DormitoryViolationAutomationService
{
    public const SOURCE_DORM_ATTENDANCE_ALPA = 'dormitory_attendance_alpa';

    public const SOURCE_ACTIVITY_ALPA = 'activity_alpa';

    public const SOURCE_ACADEMIC_ATTENDANCE_ALPA = 'academic_attendance_alpa';

    public const SOURCE_PERMIT_LATE_RETURN = 'permit_late_return';

    public function __construct(
        private readonly DormitoryPointService $points,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function sources(): array
    {
        return [
            self::SOURCE_DORM_ATTENDANCE_ALPA => 'Absensi asrama: alpa',
            self::SOURCE_ACTIVITY_ALPA => 'Kegiatan asrama: alpa',
            self::SOURCE_ACADEMIC_ATTENDANCE_ALPA => 'Kehadiran kelas: alpa',
            self::SOURCE_PERMIT_LATE_RETURN => 'Izin: kembali melewati batas waktu',
        ];
    }

    public function clearSource(string $sourceType, ?string $sourceId): void
    {
        $this->clear($sourceType, $sourceId);
    }

    public function syncDormitoryAttendance(DormitoryAttendance $record): void
    {
        if ($record->status !== 'alpa') {
            $this->clear(self::SOURCE_DORM_ATTENDANCE_ALPA, $record->id);

            return;
        }

        $this->sync(
            self::SOURCE_DORM_ATTENDANCE_ALPA,
            $record->id,
            $record->student_id,
            $record->dormitory_id,
            $record->room_id,
            $record->academic_year_id,
            optional($record->attendance_date)->toDateString() ?: now()->toDateString(),
            'Absensi asrama sesi '.$record->session.' berstatus alpa.',
            $record->recorded_by,
        );
    }

    public function syncActivityAttendance(DormitoryActivityAttendance $record): void
    {
        if ($record->status !== 'alpa') {
            $this->clear(self::SOURCE_ACTIVITY_ALPA, $record->id);

            return;
        }

        $activity = $record->activity;

        if (! $activity) {
            return;
        }

        $this->sync(
            self::SOURCE_ACTIVITY_ALPA,
            $record->id,
            $record->student_id,
            $activity->dormitory_id,
            $record->room_id,
            $activity->academic_year_id,
            optional($activity->activity_date)->toDateString() ?: now()->toDateString(),
            'Tidak hadir pada kegiatan "'.$activity->name.'".',
            $record->recorded_by,
        );
    }

    public function syncAcademicAttendance(AdminPresensiHarian $record): void
    {
        if ($record->status !== 'alpa') {
            $this->clear(self::SOURCE_ACADEMIC_ATTENDANCE_ALPA, $record->id);

            return;
        }

        $resident = DormitoryResident::query()
            ->with('room')
            ->where('student_id', $record->student_id)
            ->where('is_active', true)
            ->when($record->academic_year_id, fn ($q) => $q->where('academic_year_id', $record->academic_year_id))
            ->orderByDesc('check_in_date')
            ->first();

        if (! $resident || ! $resident->dormitory_id || ! $resident->room_id) {
            $this->clear(self::SOURCE_ACADEMIC_ATTENDANCE_ALPA, $record->id);

            return;
        }

        $this->sync(
            self::SOURCE_ACADEMIC_ATTENDANCE_ALPA,
            $record->id,
            $record->student_id,
            $resident->dormitory_id,
            $resident->room_id,
            $record->academic_year_id,
            optional($record->attendance_date)->toDateString() ?: now()->toDateString(),
            'Tidak hadir pada presensi harian kelas tanpa surat/izin.',
            $record->verified_by,
        );
    }

    public function syncPermitReturn(DormitoryPermit $permit): void
    {
        $actual = $permit->actual_return_datetime;
        $expected = $permit->expected_return_datetime;
        $isLate = $permit->status === 'returned'
            && $actual
            && $expected
            && Carbon::parse($actual)->greaterThan(Carbon::parse($expected));

        if (! $isLate) {
            $this->clear(self::SOURCE_PERMIT_LATE_RETURN, $permit->id);

            return;
        }

        $minutes = Carbon::parse($expected)->diffInMinutes(Carbon::parse($actual));

        $this->sync(
            self::SOURCE_PERMIT_LATE_RETURN,
            $permit->id,
            $permit->student_id,
            $permit->dormitory_id,
            $permit->room_id,
            $permit->academic_year_id,
            Carbon::parse($actual)->toDateString(),
            'Kembali melewati batas izin sekitar '.max(1, (int) $minutes).' menit.',
            $permit->late_return_confirmed_by ?: $permit->last_actioned_by ?: $permit->approved_by,
        );
    }

    private function sync(
        string $sourceType,
        string $sourceId,
        ?string $studentId,
        ?string $dormitoryId,
        ?string $roomId,
        ?string $academicYearId,
        string $date,
        string $description,
        ?string $actorId = null,
    ): ?DormitoryViolation {
        $automation = DormitoryPointAutomation::query()
            ->with('pointCatalog')
            ->where('source_key', $sourceType)
            ->where('is_active', true)
            ->first();

        $catalog = $automation?->pointCatalog;
        $academicYearId = $academicYearId ?: AcademicYear::where('is_active', true)->value('id');

        if (! $catalog || ! $studentId || ! $dormitoryId || ! $roomId || ! $academicYearId) {
            $this->clear($sourceType, $sourceId);

            return null;
        }

        $actorId = $actorId
            ?: auth()->id()
            ?: Dormitory::whereKey($dormitoryId)->value('head_id')
            ?: User::where('is_system_admin', true)->value('id');

        if (! $actorId) {
            return null;
        }

        $violation = DormitoryViolation::firstOrNew([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        $violation->fill([
            'student_id' => $studentId,
            'room_id' => $roomId,
            'dormitory_id' => $dormitoryId,
            'academic_year_id' => $academicYearId,
            'recorded_by' => $actorId,
            'violation_date' => $date,
            'violation_category' => $catalog->severityCategory(),
            'violation_type' => $catalog->name,
            'description' => $description,
            'points' => (int) $catalog->points_min,
            'point_catalog_id' => $catalog->id,
            'is_automatic' => true,
        ]);

        if ($violation->exists && ! $violation->isDirty()) {
            return $violation;
        }

        $violation->save();
        $this->points->recordViolation($violation);

        return $violation;
    }

    private function clear(string $sourceType, ?string $sourceId): void
    {
        if (! $sourceId) {
            return;
        }

        $violation = DormitoryViolation::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if (! $violation) {
            return;
        }

        $this->points->removeViolation($violation);
        $violation->delete();
    }
}
