<?php

namespace App\Services\Sarpras;

use App\Events\Sarpras\StockOpnameCompleted;
use App\Events\Sarpras\StockOpnameStarted;
use App\Models\Asset;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameOfficer;
use App\Models\StockOpnameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Workflow 4 — Stock Opname.
 *
 * Planned session -> assign officers -> QR-scan each asset -> mark found/missing/damaged/moved
 * -> close -> generate report -> update timelines.
 */
class StockOpnameWorkflow
{
    public const SO_STATE = 'so_state';

    public const SO_TRANSITIONS = [
        'planned' => ['in_progress', 'cancelled'],
        'in_progress' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected StateMachine $stateMachine
    ) {
        $this->stateMachine->define(self::SO_STATE, self::SO_TRANSITIONS);
    }

    /**
     * Create a new Stock Opname session and populate asset items.
     */
    public function createSession(
        User $creator,
        string $title,
        string $description,
        $scheduledDate,
        ?array $workUnitIds = null,
        ?array $assetIds = null
    ): StockOpnameSession {
        return DB::transaction(function () use (
            $creator,
            $title,
            $description,
            $scheduledDate,
            $workUnitIds,
            $assetIds
        ) {
            $session = StockOpnameSession::create([
                'work_unit_id' => $workUnitIds ? json_encode($workUnitIds) : null,
                'school_id' => $creator->school_id,
                'title' => $title,
                'description' => $description,
                'scheduled_date' => $scheduledDate,
                'status' => 'planned',
                'created_by' => $creator->id,
            ]);

            // Populate items to scan.
            if ($assetIds) {
                $assets = $this->queryAssets($assetIds);
            } elseif ($workUnitIds) {
                $assets = $this->queryAssetsByWorkUnits($workUnitIds);
            } else {
                $assets = Asset::all();
            }

            foreach ($assets as $asset) {
                $session->items()->updateOrCreate(
                    ['asset_id' => $asset->id],
                    [
                        'expected_status' => $asset->status,
                        'expected_room_id' => $asset->room_id ?? null,
                        'observed_status' => null,
                        'observed_room_id' => null,
                        'condition_observed' => null,
                    ]
                );
            }

            return $session;
        });
    }

    public function assignOfficers(StockOpnameSession $session, array $officerIds, User $assigner): void
    {
        foreach ($officerIds as $uid) {
            StockOpnameOfficer::updateOrCreate(
                ['session_id' => $session->id, 'user_id' => $uid],
                ['role' => 'officer']
            );
        }
    }

    /**
     * Move the session from planned -> in_progress (or open an existing session).
     */
    public function startSession(StockOpnameSession $session, User $operator): StockOpnameSession
    {
        $from = $session->status;
        $this->stateMachine->assert(self::SO_STATE, $from, 'in_progress');

        $session->update([
            'status' => 'in_progress',
            'started_date' => $session->started_date ?? $session->scheduled_date,
        ]);

        StockOpnameStarted::dispatch($session, $operator);

        return $session;
    }

    public function cancelSession(StockOpnameSession $session, User $canceller): StockOpnameSession
    {
        $from = $session->status;
        $this->stateMachine->assert(self::SO_STATE, $from, 'cancelled');

        $session->update(['status' => 'cancelled']);

        return $session;
    }

    /**
     * Record the observation for an item (QR scan result).
     * This is the main action called by officers during stock opname.
     */
    public function recordObservation(
        StockOpnameItem $item,
        string $observedStatus,
        string $conditionObserved,
        ?string $observedRoomId,
        ?string $notes,
        User $recorder
    ): StockOpnameItem {
        return DB::transaction(function () use (
            $item,
            $observedStatus,
            $conditionObserved,
            $observedRoomId,
            $notes,
            $recorder
        ) {
            $item->update([
                'observed_status' => $observedStatus,
                'condition_observed' => $conditionObserved,
                'observed_room_id' => $observedRoomId,
                'notes' => $notes,
                'scanned_at' => now(),
                'recorded_by' => $recorder->id,
            ]);

            // If the asset was moved, log the movement in asset_event_logs.
            if ($observedStatus === StockOpnameSession::OBSERVATION_MOVED && $observedRoomId) {
                // Update the asset's room_id to the observed location.
                $item->asset->update(['room_id' => $observedRoomId]);
            }

            // If the asset is marked missing and is currently active, flag it.
            if ($observedStatus === StockOpnameSession::OBSERVATION_MISSING && $item->asset->status === 'active') {
                $item->asset->update(['status' => 'lost']);
                $this->eventLogger->logAssetStatusChanged(
                    $item->asset,
                    'active',
                    'lost',
                    'stock opname: marked missing',
                    $recorder->id
                );
            }

            return $item;
        });
    }

    /**
     * Close the session, write the summary, and update asset timelines for discrepancies.
     */
    public function closeSession(StockOpnameSession $session, User $closer): StockOpnameSession
    {
        return DB::transaction(function () use ($session, $closer) {
            $from = $session->status;
            $this->stateMachine->assert(self::SO_STATE, $from, 'closed');

            $session->update([
                'status' => 'closed',
                'closed_date' => now(),
                'closed_by' => $closer->id,
            ]);

            StockOpnameCompleted::dispatch($session, $closer);

            // Log an aggregate event for all assets that were found/damaged but not closed.
            $discrepancies = $session->items()
                ->whereIn('observed_status', [
                    StockOpnameSession::OBSERVATION_MISSING,
                    StockOpnameSession::OBSERVATION_DAMAGED,
                ])
                ->whereNotNull('observed_status')
                ->get();

            foreach ($discrepancies as $item) {
                $this->eventLogger->logStockOpnameOpened(
                    $item->asset,
                    $session->session_code,
                    $closer->id
                );
            }

            return $session;
        });
    }

    /**
     * Fetch the full session report with breakdown.
     */
    public function getReport(StockOpnameSession $session): array
    {
        return $session->report();
    }

    /* ---- helpers ---- */

    protected function queryAssets(?array $ids): \Illuminate\Database\Eloquent\Collection
    {
        return Asset::whereIn('id', $ids)->get();
    }

    protected function queryAssetsByWorkUnits(?array $unitIds): \Illuminate\Database\Eloquent\Collection
    {
        return Asset::whereIn('work_unit_id', $unitIds)->get();
    }
}
