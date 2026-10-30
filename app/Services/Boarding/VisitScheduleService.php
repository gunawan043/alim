<?php

namespace App\Services\Boarding;

use App\Models\DormitoryVisitSchedule;
use App\Models\Student;
use Carbon\CarbonImmutable;

class VisitScheduleService
{
    public const TIMEZONE = 'Asia/Makassar';

    /**
     * Jadwal fallback bila belum ada konfigurasi per asrama.
     *
     * @return array<int, array{week:int, start:string, end:string, label:string}>
     */
    public function defaultSlots(string $gender): array
    {
        $weeks = $gender === 'putri' ? [2, 4] : [1, 3];

        return collect($weeks)
            ->flatMap(fn (int $week) => [
                ['week' => $week, 'start' => '08:00', 'end' => '11:30', 'label' => "Ahad pekan {$week} — pagi"],
                ['week' => $week, 'start' => '15:00', 'end' => '17:30', 'label' => "Ahad pekan {$week} — sore"],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{allowed: bool, reason: ?string, slots: array<int, array<string, mixed>>}
     */
    public function validate(Student $student, string|\DateTimeInterface $arrival, ?string $dormitoryId = null): array
    {
        $at = CarbonImmutable::parse($arrival)->setTimezone(self::TIMEZONE);
        $gender = $student->gender === 'L' ? 'putra' : 'putri';
        $week = (int) ceil($at->day / 7);
        $day = (int) $at->dayOfWeek; // 0 = Ahad

        $configured = DormitoryVisitSchedule::query()
            ->where('is_active', true)
            ->where('gender', $gender)
            ->where('week_of_month', $week)
            ->where('day_of_week', $day)
            ->where(function ($query) use ($dormitoryId) {
                $query->whereNull('dormitory_id');
                if ($dormitoryId) {
                    $query->orWhere('dormitory_id', $dormitoryId);
                }
            })
            ->orderByRaw('dormitory_id IS NULL') // jadwal khusus asrama lebih dulu
            ->get();

        $slots = $configured->map(fn (DormitoryVisitSchedule $schedule) => [
            'start' => substr((string) $schedule->start_time, 0, 5),
            'end' => substr((string) $schedule->end_time, 0, 5),
            'label' => $schedule->label ?: 'Jadwal kunjungan',
        ])->all();

        if ($slots === []) {
            $slots = collect($this->defaultSlots($gender))
                ->where('week', $week)
                ->map(fn (array $slot) => [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'label' => $slot['label'],
                ])
                ->values()
                ->all();
        }

        if ($week === 5) {
            return [
                'allowed' => false,
                'reason' => 'Pekan ke-5 adalah pekan libur kunjungan.',
                'slots' => $slots,
            ];
        }

        if ($slots === []) {
            return [
                'allowed' => false,
                'reason' => "Kunjungan santri {$gender} pada pekan ke-{$week} tidak dijadwalkan.",
                'slots' => $slots,
            ];
        }

        $time = $at->format('H:i');
        $matches = collect($slots)->contains(fn (array $slot) => $time >= $slot['start'] && $time <= $slot['end']);

        return [
            'allowed' => $matches,
            'reason' => $matches ? null : 'Kunjungan di luar jam yang dijadwalkan. Slot: '.$this->formatSlots($slots),
            'slots' => $slots,
        ];
    }

    public function formatSlots(array $slots): string
    {
        return collect($slots)
            ->map(fn (array $slot) => ($slot['start'] ?? '').'–'.($slot['end'] ?? ''))
            ->implode(', ');
    }
}
