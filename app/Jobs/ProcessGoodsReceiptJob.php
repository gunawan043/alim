<?php

namespace App\Jobs;

use App\Models\GoodsReceipt;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGoodsReceiptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $goodsReceiptId
    ) {}

    public function handle(): void
    {
        $gr = GoodsReceipt::with('items')->find($this->goodsReceiptId);
        if (! $gr) {
            return;
        }

        // Process stock entries
        $warehouseService = app(\App\Services\WarehouseService::class);
        foreach ($gr->items as $item) {
            if ($item->quality_check_passed ?? true) {
                $warehouseService->addStock(
                    $item->product_id,
                    $item->warehouse_id,
                    $item->quantity,
                    "GR-{$gr->receipt_number}",
                    auth()->id()
                );
            }
        }

        Notification::create([
            'user_id' => $gr->received_by,
            'type' => 'goods_processed',
            'title' => 'Goods Receipt Processed',
            'message' => "Goods receipt {$gr->receipt_number} has been processed",
            'data' => ['gr_id' => $gr->id],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure
    }
}
