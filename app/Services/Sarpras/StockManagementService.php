<?php

namespace App\Services\Sarpras;

use App\Models\Sparepart;
use App\Models\SparepartReservation;
use App\Models\SparepartStockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockManagementService
{
    /**
     * Reserve sparepart for a work order or repair request.
     */
    public function reserve(Sparepart $part, float $qty, string $refType, string $refId, User $actor): array
    {
        return DB::transaction(function () use ($part, $qty, $refType, $refId, $actor) {
            $available = $part->available;
            if ($available < $qty) {
                return ['success' => false, 'error' => 'Insufficient stock'];
            }

            $reservation = SparepartReservation::create([
                'sparepart_id' => $part->id,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'quantity' => $qty,
                'consumed_quantity' => 0,
                'reserved_by' => $actor->id,
                'expires_at' => now()->addDays($part->lead_time_days ?? 7),
                'status' => 'active',
            ]);

            return ['success' => true, 'reservation' => $reservation];
        });
    }

    /**
     * Consume reservations (issue parts).
     */
    public function consume(Sparepart $part, float $qty, string $refType, string $refId, User $actor): array
    {
        return DB::transaction(function () use ($part, $qty, $refType, $refId, $actor) {
            $reservations = $part->reservations()
                ->where('status', 'active')
                ->where('reference_type', $refType)
                ->where('reference_id', $refId)
                ->get();

            $remaining = $qty;
            $movements = [];

            foreach ($reservations as $res) {
                if ($remaining <= 0) {
                    break;
                }

                $canConsume = min($res->quantity - $res->consumed_quantity, $remaining);
                if ($canConsume <= 0) {
                    continue;
                }

                $res->consumed_quantity += $canConsume;
                if ($res->consumed_quantity >= $res->quantity) {
                    $res->status = 'consumed';
                }
                $res->save();

                $movements[] = [
                    'movement_code' => null,
                    'sparepart_id' => $part->id,
                    'movement_type' => 'issue',
                    'quantity' => -$canConsume,
                    'balance_after' => 0, // updated below
                    'unit_cost' => $part->unit_price,
                    'total_cost' => $canConsume * $part->unit_price,
                    'from_warehouse_id' => $part->warehouse_id,
                    'from_bin_id' => $part->bin_id,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'performed_by' => $actor->id,
                    'reason' => 'Consumed by reservation '.$res->id,
                ];
                $remaining -= $canConsume;
            }

            if ($remaining > 0) {
                return ['success' => false, 'error' => 'Could only consume '.($qty - $remaining).' of '.$qty];
            }

            foreach ($movements as &$m) {
                // Balance is recomputed separately
            }

            return ['success' => true, 'movements' => $movements];
        });
    }

    /**
     * Receive stock (from purchase order or direct).
     */
    public function receive(int $sparepartId, float $qty, int $warehouseId, ?int $binId, User $actor, string $reason = ''): SparepartStockMovement
    {
        return DB::transaction(function () use ($sparepartId, $qty, $warehouseId, $binId, $actor, $reason) {
            $part = Sparepart::lockForUpdate()->findOrFail($sparepartId);
            $oldStock = $part->stock;
            $part->stock = bcadd((string) $oldStock, (string) $qty, 2);

            if ($part->reorder_point > 0) {
                $part->min_stock = max($part->min_stock, $part->reorder_point * 0.5);
            }

            $part->save();

            $movement = SparepartStockMovement::create([
                'sparepart_id' => $sparepartId,
                'movement_type' => 'receive',
                'quantity' => $qty,
                'balance_after' => $part->stock,
                'unit_cost' => $part->unit_price,
                'total_cost' => $qty * $part->unit_price,
                'from_warehouse_id' => null,
                'to_warehouse_id' => $warehouseId,
                'from_bin_id' => null,
                'to_bin_id' => $binId,
                'performed_by' => $actor->id,
                'reason' => $reason,
            ]);

            event(new \App\Events\Sarpras\SparepartReceived($part, $movement, $actor));

            return $movement;
        });
    }

    /**
     * Transfer stock between warehouses or bins.
     */
    public function transfer(int $sparepartId, float $qty, int $fromWarehouseId, int $toWarehouseId, ?int $fromBinId, ?int $toBinId, User $actor): array
    {
        return DB::transaction(function () use ($sparepartId, $qty, $fromWarehouseId, $toWarehouseId, $fromBinId, $toBinId, $actor) {
            $part = Sparepart::lockForUpdate()->findOrFail($sparepartId);

            $available = $part->available;
            if ($available < $qty) {
                return ['success' => false, 'error' => 'Insufficient stock for transfer'];
            }

            $part->stock = bcsub((string) $part->stock, (string) $qty, 2);
            $part->save();

            // Record outgoing movement
            $out = SparepartStockMovement::create([
                'sparepart_id' => $sparepartId,
                'movement_type' => 'transfer',
                'quantity' => -$qty,
                'balance_after' => $part->stock,
                'unit_cost' => $part->average_cost,
                'total_cost' => $qty * $part->average_cost,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'from_bin_id' => $fromBinId,
                'to_bin_id' => $toBinId,
                'performed_by' => $actor->id,
            ]);

            // Record incoming movement at destination
            $destPart = Sparepart::whereId($sparepartId)
                ->where('warehouse_id', $toWarehouseId)
                ->first();
            if ($destPart) {
                $destPart->stock = bcadd((string) $destPart->stock, (string) $qty, 2);
                $destPart->save();

                SparepartStockMovement::create([
                    'sparepart_id' => $sparepartId,
                    'movement_type' => 'transfer',
                    'quantity' => $qty,
                    'balance_after' => $destPart->stock,
                    'unit_cost' => $destPart->average_cost,
                    'total_cost' => $qty * $destPart->average_cost,
                    'from_warehouse_id' => $fromWarehouseId,
                    'to_warehouse_id' => $toWarehouseId,
                    'from_bin_id' => $fromBinId,
                    'to_bin_id' => $toBinId,
                    'performed_by' => $actor->id,
                ]);
            }

            return ['success' => true, 'outbound_movement' => $out];
        });
    }

    /**
     * Adjustment (manual correction).
     */
    public function adjust(int $sparepartId, float $newStock, User $actor, string $reason = ''): SparepartStockMovement
    {
        return DB::transaction(function () use ($sparepartId, $newStock, $actor, $reason) {
            $part = Sparepart::lockForUpdate()->findOrFail($sparepartId);
            $diff = bcsub((string) $newStock, (string) $part->stock, 2);

            $part->stock = $newStock;
            $part->save();

            $movement = SparepartStockMovement::create([
                'sparepart_id' => $sparepartId,
                'movement_type' => 'adjustment',
                'quantity' => $diff,
                'balance_after' => $newStock,
                'unit_cost' => $part->average_cost,
                'total_cost' => abs($diff) * $part->average_cost,
                'performed_by' => $actor->id,
                'reason' => $reason ?: 'Stock adjustment: '.$part->stock.' → '.$newStock,
            ]);

            event(new \App\Events\Sarpras\SparepartAdjusted($part, $movement, $actor));

            return $movement;
        });
    }
}
