<?php

namespace App\Services\Boarding;

use App\Models\BoardingTimelineEvent;
use App\Models\DormitoryPermit;
use App\Models\DormitoryVisitLog;
use App\Models\StudentHealthPermit;
use Carbon\CarbonImmutable;

/**
 * Boarding Approval Center — a unified inbox that aggregates pending
 * approvals across all boarding sub-domains (leave, visit, health, room move).
 *
 * Approve/reject actions are delegated to the respective domain workflow
 * services — no duplicate state, no secondary approval table.
 *
 * The service itself is read-only for the index/show; mutation is handled
 * by the controllers which call through to workflow services.
 */
class BoardingApprovalService
{
    public function __construct(
        private readonly LeaveWorkflowService $leave,
        private readonly VisitWorkflowService $visits,
        private readonly HealthWorkflowService $health,
    ) {}

    /**
     * Aggregate all pending approvals into a single list, sorted newest-first.
     */
    public function pending(int $dormitoryId): array
    {
        $items = [];

        // ── Leave Requests ────────────────────────────────────────────
        $permits = DormitoryPermit::with(['student:id,name', 'requestedBy:id,name', 'dormitory:id,name'])
            ->where('dormitory_id', $dormitoryId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        foreach ($permits as $p) {
            $items[] = [
                'id' => $p->id,
                'type' => 'leave',
                'type_label' => 'Izin Pulang',
                'title' => "{$p->student->name} — Izin Pulang",
                'detail' => $p->description ?? $p->permit_type,
                'started_at' => $p->created_at?->toIso8601String(),
                'student_id' => $p->student_id,
                'student_name' => $p->student?->name,
                'requested_by' => $p->requested_by,
                'requester_name' => $p->requestedBy?->name ?? ($p->requested_by ?: '—'),
                'extra' => [
                    'return_at' => $p->expected_return_at?->toIso8601String(),
                    'mahrom_required' => $p->requires_mahrom,
                ],
            ];
        }

        // ── Visit Requests ────────────────────────────────────────────
        $visits = DormitoryVisitLog::with(['student:id,name', 'visitor:id,name', 'guardian:id,name', 'dormitory:id,name'])
            ->where('dormitory_id', $dormitoryId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        foreach ($visits as $v) {
            $items[] = [
                'id' => $v->id,
                'type' => 'visit',
                'type_label' => 'Permohonan Penjengukan',
                'title' => "{$v->student->name} — Penjengukan oleh {$v->visitor_name}",
                'detail' => $v->purpose ?? $v->visitors,
                'started_at' => $v->created_at?->toIso8601String(),
                'student_id' => $v->student_id,
                'student_name' => $v->student?->name,
                'requested_by' => $v->visitor_id,
                'requester_name' => $v->visitor_name,
                'extra' => [
                    'visit_from' => $v->visit_from?->toIso8601String(),
                    'visit_to' => $v->visit_until?->toIso8601String(),
                    'visitor_count' => $v->visitor_count,
                ],
            ];
        }

        // ── Health Requests ─────────────────────────────────────��─────
        $permits = StudentHealthPermit::with(['student:id,name', 'dormitory:id,name', 'creator:id,name'])
            ->where('dormitory_id', $dormitoryId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        foreach ($permits as $p) {
            $items[] = [
                'id' => $p->id,
                'type' => 'health',
                'type_label' => 'Izin Sakit / Rawat Jalan',
                'title' => "{$p->student->name} — Izin Sakit ({$p->permit_type})",
                'detail' => $p->description,
                'started_at' => $p->created_at?->toIso8601String(),
                'student_id' => $p->student_id,
                'student_name' => $p->student?->name,
                'requested_by' => $p->created_by,
                'requester_name' => $p->creator?->name ?? '—',
                'extra' => [
                    'start_date' => $p->start_date?->toIso8601String(),
                    'end_date' => $p->end_date?->toIso8601String(),
                ],
            ];
        }

        // Sort all items by created_at (most recent first)
        usort($items, fn ($a, $b) => $b['started_at'] <=> $a['started_at']);

        return $items;
    }

    /**
     * Count items by type for summary cards.
     */
    public function counts(int $dormitoryId): array
    {
        return [
            'leave' => DormitoryPermit::where('dormitory_id', $dormitoryId)
                ->where('status', 'pending')->count(),
            'visit' => DormitoryVisitLog::where('dormitory_id', $dormitoryId)
                ->where('status', 'pending')->count(),
            'health' => StudentHealthPermit::where('dormitory_id', $dormitoryId)
                ->where('status', 'pending')->count(),
            'total' => 0,
        ];
    }
}