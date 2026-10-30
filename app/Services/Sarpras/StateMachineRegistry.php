<?php

namespace App\Services\Sarpras;

class StateMachineRegistry
{
    public const REPAIR_REQUEST = 'repair_request';

    public const WORK_ORDER = 'work_order';

    public const STOCK_OPNAME_SESSION = 'stock_opname_session';

    public const STOCK_OPNAME_ITEM = 'stock_opname_item';

    public const ASSET_STATUS = 'asset_status';

    public const MAINTENANCE_SCHEDULE = 'maintenance_schedule';

    public const MAINTENANCE_LOG = 'maintenance_log';

    /* =========================================================================
     *  Repair Request (Damage Reporting → Repair Completion)
     * =========================================================================
     *  Status mengikuti RepairRequest::STATUS_* — kosakata yang dipakai
     *  controller (SarprasRepairController) dan view (sarpras.repair.*):
     *
     *  submitted -> verification_pending -> verification_in_progress
     *            -> approval_pending -> execution_pending -> started
     *            -> completed -> closed
     *  Ditambah cabang additional_info / stopped dan terminal
     *  verification_rejected / approval_rejected.
     * ========================================================================= */
    public const REPAIR_REQUEST_TRANSITIONS = [
        'submitted' => ['verification_pending', 'verification_rejected', 'additional_info'],
        'verification_pending' => ['verification_in_progress', 'approval_pending', 'verification_rejected', 'additional_info'],
        'verification_in_progress' => ['approval_pending', 'verification_rejected', 'additional_info'],
        'additional_info' => ['verification_pending', 'verification_in_progress', 'verification_rejected'],
        'approval_pending' => ['execution_pending', 'approval_rejected'],
        'execution_pending' => ['started', 'completed', 'approval_rejected'],
        'started' => ['completed', 'stopped'],
        'stopped' => ['started'],
        'completed' => ['closed'],
        'closed' => [],
        'verification_rejected' => [],
        'approval_rejected' => [],
    ];

    /* =========================================================================
     *  Work Order
     *  Status mengikuti work_orders.status:
     *  draft, dispatched, in_progress, paused, waiting_parts, completed,
     *  closed, cancelled
     * ========================================================================= */
    public const WORK_ORDER_TRANSITIONS = [
        'draft' => ['dispatched', 'cancelled'],
        'dispatched' => ['in_progress', 'cancelled'],
        'in_progress' => ['paused', 'waiting_parts', 'completed', 'cancelled'],
        'paused' => ['in_progress', 'cancelled'],
        'waiting_parts' => ['in_progress', 'cancelled'],
        'completed' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    /* =========================================================================
     *  Stock Opname Session
     * ========================================================================= */
    public const STOCK_OPNAME_SESSION_TRANSITIONS = [
        'planned' => ['in_progress', 'cancelled'],
        'in_progress' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    /* =========================================================================
     *  Stock Opname Item
     * ========================================================================= */
    public const STOCK_OPNAME_ITEM_TRANSITIONS = [
        'pending' => ['found', 'missing', 'damaged', 'location_mismatch'],
        'found' => ['location_mismatch'],
        'missing' => ['found'],
        'damaged' => ['found'],
        'location_mismatch' => ['found'],
    ];

    /* =========================================================================
     *  Asset status — informational transitions driven by workflow outcomes
     * ========================================================================= */
    public const ASSET_STATUS_TRANSITIONS = [
        'active' => ['borrowed', 'under_maintenance', 'under_repair', 'damaged', 'disposed', 'lost'],
        'borrowed' => ['active', 'damaged'],
        'under_maintenance' => ['active', 'damaged'],
        'under_repair' => ['active', 'damaged', 'disposed'],
        'damaged' => ['under_repair', 'disposed'],
        'disposed' => [],
        'lost' => ['active', 'disposed'],
    ];

    /* =========================================================================
     *  Maintenance Schedule
     * ========================================================================= */
    public const MAINTENANCE_SCHEDULE_TRANSITIONS = [
        'active' => ['paused', 'inactive', 'overdue'],
        'paused' => ['active'],
        'inactive' => ['active'],
        'overdue' => ['active'],
    ];

    /* =========================================================================
     *  Maintenance Log
     * ========================================================================= */
    public const MAINTENANCE_LOG_TRANSITIONS = [
        'scheduled' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'interrupted'],
        'completed' => [],
        'cancelled' => [],
        'interrupted' => ['scheduled'],
    ];

    public static function transitionsFor(string $model): array
    {
        return match ($model) {
            self::REPAIR_REQUEST => self::REPAIR_REQUEST_TRANSITIONS,
            self::WORK_ORDER => self::WORK_ORDER_TRANSITIONS,
            self::STOCK_OPNAME_SESSION => self::STOCK_OPNAME_SESSION_TRANSITIONS,
            self::STOCK_OPNAME_ITEM => self::STOCK_OPNAME_ITEM_TRANSITIONS,
            self::ASSET_STATUS => self::ASSET_STATUS_TRANSITIONS,
            self::MAINTENANCE_SCHEDULE => self::MAINTENANCE_SCHEDULE_TRANSITIONS,
            self::MAINTENANCE_LOG => self::MAINTENANCE_LOG_TRANSITIONS,
            default => [],
        };
    }

    public static function assertValidTransition(string $model, string $from, string $to): void
    {
        $allowed = self::transitionsFor($model)[$from] ?? [];
        if (! in_array($to, $allowed, true)) {
            throw new IllegalStateTransitionException(
                "Illegal transition [{$model}]: {$from} -> {$to} (allowed: ".implode(', ', $allowed).')'
            );
        }
    }

    public static function canTransition(string $model, string $from, string $to): bool
    {
        $allowed = self::transitionsFor($model)[$from] ?? [];

        return in_array($to, $allowed, true);
    }

    public static function isTerminal(string $model, string $status): bool
    {
        return self::transitionsFor($model)[$status] === [];
    }

    public static function getNextStates(string $model, string $current): array
    {
        return self::transitionsFor($model)[$current] ?? [];
    }
}
