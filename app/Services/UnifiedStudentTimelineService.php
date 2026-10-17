<?php

namespace App\Services;

use App\Models\BoardingTimelineEvent;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Unified Student Timeline Service — aggregates events from ALL modules
 * into a single chronological stream for any student.
 *
 * This is the SINGLE source of truth for the student's life journey
 * across Boarding, Academic, Clinic, Violation, Reward, Library, etc.
 *
 * Modules contribute events by:
 * 1. Inserting a row into boarding_timeline_events with `module` + `category` columns
 * 2. Publishing a domain event that listeners catch to enrich timeline
 *
 * The timeline service only READS from the unified table.
 */
class UnifiedStudentTimelineService
{
    public function __construct(
        private readonly Student $student,
    ) {}

    /**
     * Get the complete timeline for this student.
     * Returns a grouped Collection by year => events[].
     */
    public function getTimeline(): Collection
    {
        $events = BoardingTimelineEvent::where('student_id', $this->student->id)
            ->orderByDesc('event_at')
            ->get();

        return $events
            ->sortByDesc(fn (BoardingTimelineEvent $e) => $e->event_at->year())
            ->groupBy(fn (BoardingTimelineEvent $e) => $e->event_at->year())
            ->map(fn (Collection $yearEvents) => $yearEvents
                ->values()
            );
    }

    /**
     * Get all timeline events flat-ordered by date (descending).
     */
    public function getAllEvents(): Collection
    {
        return BoardingTimelineEvent::where('student_id', $this->student->id)
            ->orderByDesc('event_at')
            ->get()
            ->map(fn (BoardingTimelineEvent $e) => $this->enrichEvent($e));
    }

    /**
     * Filter timeline by module.
     */
    public function getByModule(string $module): Collection
    {
        return BoardingTimelineEvent::where('student_id', $this->student->id)
            ->where('module', $module)
            ->orderByDesc('event_at')
            ->get()
            ->map(fn (BoardingTimelineEvent $e) => $this->enrichEvent($e));
    }

    /**
     * Get counts per module for a summary bar.
     */
    public function getModuleBreakdown(): Collection
    {
        return BoardingTimelineEvent::where('student_id', $this->student->id)
            ->selectRaw('module, COUNT(*) as cnt')
            ->groupBy('module')
            ->get()
            ->pluck('cnt', 'module');
    }

    /**
     * Enrich a raw timeline event with human-readable metadata.
     */
    private function enrichEvent(BoardingTimelineEvent $event): array
    {
        $moduleIcon = $this->resolveModuleIcon($event->module);
        $moduleColor = $this->resolveModuleColor($event->module);

        return [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'module' => $event->module,
            'category' => $event->category,
            'title' => $this->resolveEventTitle($event),
            'subtitle' => $this->resolveEventSubtitle($event),
            'event_at' => $event->event_at,
            'icon' => $moduleIcon,
            'color' => $moduleColor,
            'payload' => $event->payload,
            'subject_refs' => $event->subject_refs,
            'isSpecialPermission' => (bool) $event->is_special_permission,
        ];
    }

    private function resolveModuleIcon(string $module): string
    {
        return match ($module) {
            'boarding' => 'ri-home-heart-line',
            'academic' => 'ri-book-open-line',
            'clinic' => 'ri-hospital-line',
            'violation' => 'ri-alert-line',
            'reward' => 'ri-trophy-line',
            'library' => 'ri-book-mark-line',
            'sarpras' => 'ri-tools-line',
            'system' => 'ri-settings-4-line',
            default => 'ri-circle-line',
        };
    }

    private function resolveModuleColor(string $module): string
    {
        return match ($module) {
            'boarding' => 'info',
            'academic' => 'primary',
            'clinic' => 'warning',
            'violation' => 'danger',
            'reward' => 'success',
            'library' => 'dark',
            'sarpras' => 'secondary',
            'system' => 'muted',
            default => 'muted',
        };
    }

    /**
     * Human-readable title based on event_type + module.
     */
    private function resolveEventTitle(BoardingTimelineEvent $event): string
    {
        $titles = [
            // Boarding
            'check_in' => 'Masuk Asrama',
            'check_out' => 'Keluar Asrama',
            'room_transfer' => 'Pindah Kamar',
            'leave_approved' => 'Perjalanan Diterima',
            'leave_started' => 'Mulai Perjalanan',
            'leave_overdue' => 'Keterlambatan Kembali',
            'returned' => 'Kembali ke Asrama',
            'hospitalized' => 'Dirawat di Rumah Sakit',
            'recovered' => 'Sembuh & Kembali',
            'visit_approved' => 'Penjengukan Diterima',
            'visit_rejected' => 'Penjengukan Ditolak',
            'visit_check_in' => 'Tamu Check-in',
            'visit_check_out' => 'Tamu Check-out',
            'violation' => 'Pelanggaran',
            'reward' => 'Penghargaan',
            'expelled' => 'Dikeluarkan',
            'transfer' => 'Pindah Asrama',
            'holiday' => 'Izin Cuti',
            'permit_rejected' => 'Izin Ditolak',

            // Generic fallback
        ];

        if (isset($titles[$event->event_type])) {
            return $titles[$event->event_type];
        }

        // Fallback: derive from event_type
        return ucwords(str_replace('_', ' ', $event->event_type));
    }

    /**
     * Human-readable subtitle with contextual details.
     */
    private function resolveEventSubtitle(BoardingTimelineEvent $event): ?string
    {
        if (! $event->payload) {
            return null;
        }

        $payload = (array) $event->payload;

        return match ($event->event_type) {
            'room_transfer' => sprintf('Dari: %s → Ke: %s',
                $payload['old_room'] ?? '—',
                $payload['new_room'] ?? '—',
            ),
            'leave_approved' => sprintf('%s sejak %s',
                $payload['permit_type'] ?? 'Izin Pulang',
                $payload['start_date'] ?? '',
            ),
            'hospitalized' => sprintf('Tipe: %s', $payload['permit_type'] ?? '—'),
            'violation' => sprintf('%s (%s poin)',
                $payload['violation_type'] ?? 'Pelanggaran',
                $payload['points'] ?? 0,
            ),
            'reward' => sprintf('%s — %s',
                $payload['reward_type'] ?? 'Penghargaan',
                $payload['description'] ?? '',
            ),
            'check_in' => sprintf('Check-in %s', $event->event_at->format('d M Y H:i')),
            'check_out' => sprintf('Check-out %s', $event->event_at->format('d M Y H:i')),
            default => null,
        };
    }

    /**
     * Get a summary count of all timeline events.
     */
    public function getSummary(): array
    {
        $total = BoardingTimelineEvent::where('student_id', $this->student->id)->count();
        $modules = $this->getModuleBreakdown();

        return [
            'total_events' => $total,
            'by_module' => $modules,
        ];
    }
}
