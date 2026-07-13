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
     *  draft -> submitted
     *  submitted -> verified | rejected
     *  verified -> waiting_work_order
     *  rejected -> (terminal)
     *  waiting_work_order -> assigned
     *  assigned -> in_progress
     *  in_progress -> completed
     *  completed -> verified_by_pic
     *  verified_by_pic -> closed
     * ========================================================================= */
    public const REPAIR_REQUEST_TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['verified', 'rejected'],
        'verified' => ['waiting_work_order'],
        'rejected' => [],
        'waiting_work_order' => ['assigned'],
        'assigned' => ['in_progress'],
        'in_progress' => ['completed'],
        'completed' => ['verified_by_pic'],
        'verified_by_pic' => ['closed'],
        'closed' => [],
    ];

    /* =========================================================================
     *  Work Order
     *  created -> assigned -> accepted/working/waiting_sparepart -> completed -> closed
     * ========================================================================= */
    public const WORK_ORDER_TRANSITIONS = [
        'created' => ['assigned'],
        'assigned' => ['accepted', 'cancelled'],
        'accepted' => ['working', 'waiting_sparepart'],
        'waiting_sparepart' => ['working'],
        'working' => ['completed'],
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
