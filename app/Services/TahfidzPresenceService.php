<?php

namespace App\Services;

use App\Models\AdminPresensiHarian;
use App\Models\DormitoryPermit;
use App\Models\StudentHealthPermit;
use App\Models\TahfidzSetoran;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Indikator kehadiran santri untuk halaqah Tahfidz.
 *
 * Sumber:
 * - setoran Tahfidz hari itu → sudah setor / hadir
 * - absensi harian wali kelas
 * - izin asrama
 * - izin kesehatan/UKS
 *
 * Service ini tidak menulis absensi halaqah; ia hanya menyediakan indikator
 * agar guru melihat alasan santri belum/tidak setor.
 */
class TahfidzPresenceService
{
    public function forStudent(string $studentId, Carbon|string|null $date = null): array
    {
        return $this->forStudents([$studentId], $date)->get($studentId, $this->emptyStatus());
    }

    /**
     * @param  iterable<string>  $studentIds
     * @return Collection<string, array{status:string,label:string,color:string,source:string,attendance_status:?string,has_setoran:bool}>
     */
    public function forStudents(iterable $studentIds, Carbon|string|null $date = null): Collection
    {
        $ids = collect($studentIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $day = $date instanceof Carbon
            ? $date->copy()
            : Carbon::parse($date ?: now());

        $day = $day->startOfDay();
        $endOfDay = $day->copy()->endOfDay();
        $result = $ids->mapWithKeys(fn ($id) => [(string) $id => $this->emptyStatus()]);

        try {
            $setoranIds = TahfidzSetoran::query()
                ->whereIn('student_id', $ids)
                ->whereDate('setoran_date', $day->toDateString())
                ->pluck('student_id')
                ->map(fn ($id) => (string) $id)
                ->unique();

            $daily = AdminPresensiHarian::query()
                ->whereIn('student_id', $ids)
                ->whereDate('attendance_date', $day->toDateString())
                ->get()
                ->keyBy(fn ($row) => (string) $row->student_id);

            $dormitoryPermits = DormitoryPermit::query()
                ->whereIn('student_id', $ids)
                ->whereIn('status', ['approved', 'overdue', 'picked_up'])
                ->where('departure_datetime', '<=', $endOfDay)
                ->where(function ($query) use ($day) {
                    $query->whereNull('expected_return_datetime')
                        ->orWhere('expected_return_datetime', '>=', $day);
                })
                ->orderByDesc('departure_datetime')
                ->get()
                ->groupBy(fn ($row) => (string) $row->student_id);

            $healthPermits = StudentHealthPermit::query()
                ->whereIn('student_id', $ids)
                ->whereIn('status', ['approved', 'extended'])
                ->whereDate('start_date', '<=', $day->toDateString())
                ->where(function ($query) use ($day) {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $day->toDateString());
                })
                ->orderByDesc('start_date')
                ->get()
                ->groupBy(fn ($row) => (string) $row->student_id);
        } catch (Throwable) {
            return $result;
        }

        foreach ($ids as $id) {
            $id = (string) $id;

            if ($setoranIds->contains($id)) {
                $result[$id] = $this->status(
                    'sudah_setor', 'Sudah setor', 'success', 'tahfidz', 'hadir', true
                );

                continue;
            }

            $health = $healthPermits->get($id)?->first();
            if ($health) {
                $result[$id] = $this->status(
                    'sakit', 'Sakit (UKS)', 'warning', 'uks', 'sakit', false
                );

                continue;
            }

            $permit = $dormitoryPermits->get($id)?->first();
            if ($permit) {
                $isSick = in_array($permit->permit_type, ['sakit', 'berobat'], true);
                $result[$id] = $isSick
                    ? $this->status('sakit', 'Sakit (izin asrama)', 'warning', 'asrama', 'sakit', false)
                    : $this->status('izin', 'Izin (asrama)', 'info', 'asrama', 'izin', false);

                continue;
            }

            $dailyRow = $daily->get($id);
            if ($dailyRow) {
                $status = match ($dailyRow->status) {
                    'izin' => $this->status('izin', 'Izin (wali kelas)', 'info', 'wali_kelas', 'izin', false),
                    'sakit' => $this->status('sakit', 'Sakit (wali kelas)', 'warning', 'wali_kelas', 'sakit', false),
                    'alpa' => $this->status('alpa', 'Alpa (wali kelas)', 'danger', 'wali_kelas', 'alpa', false),
                    'terlambat' => $this->status('terlambat', 'Terlambat (wali kelas)', 'warning', 'wali_kelas', 'hadir', false),
                    default => $this->status('belum_setor', 'Hadir, belum setor', 'secondary', 'wali_kelas', 'hadir', false),
                };

                $result[$id] = $status;

                continue;
            }

            $result[$id] = $this->status(
                'belum_setor', 'Belum ada setoran', 'secondary', 'tahfidz', null, false
            );
        }

        return $result;
    }

    private function emptyStatus(): array
    {
        return $this->status('belum_setor', 'Belum ada setoran', 'secondary', 'tahfidz', null, false);
    }

    private function status(
        string $status,
        string $label,
        string $color,
        string $source,
        ?string $attendanceStatus,
        bool $hasSetoran,
    ): array {
        return [
            'status' => $status,
            'label' => $label,
            'color' => $color,
            'source' => $source,
            'attendance_status' => $attendanceStatus,
            'has_setoran' => $hasSetoran,
        ];
    }
}
