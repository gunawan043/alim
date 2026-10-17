<?php

namespace App\Providers;

use App\Domain\Contracts\Repositories\GoodsReceiptRepositoryInterface;
use App\Domain\Contracts\Repositories\InvoiceApprovalRepositoryInterface;
use App\Domain\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Domain\Contracts\Repositories\QuotationRepositoryInterface;
use App\Domain\Contracts\Repositories\RfqRepositoryInterface;
use App\Domain\Contracts\Repositories\VendorRepositoryInterface;
use App\Domain\Contracts\Repositories\WarehouseRepositoryInterface;
use App\Repositories\GoodsReceiptRepository;
use App\Repositories\InvoiceApprovalRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\QuotationRepository;
use App\Repositories\RfqRepository;
use App\Repositories\VendorRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->bind(RfqRepositoryInterface::class, RfqRepository::class);
        $this->app->bind(QuotationRepositoryInterface::class, QuotationRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(GoodsReceiptRepositoryInterface::class, GoodsReceiptRepository::class);
        $this->app->bind(InvoiceApprovalRepositoryInterface::class, InvoiceApprovalRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
    }
}
