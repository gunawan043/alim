<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class InvoiceApprovalDto implements Arrayable
{
    public function __construct(
        public int $vendorId,
        public ?int $purchaseOrderId = null,
        public ?string $invoiceNumber = null,
        public ?string $supplierInvoiceNumber = null,
        public float $totalAmount = 0.0,
        public string $currency = 'IDR',
        public float $taxAmount = 0.0,
        public float $discountAmount = 0.0,
        public string $invoiceDate = '',
        public ?string $dueDate = null,
        public ?string $notes = null,
        public ?string $attachmentPath = null,
        public array $reviewers = [],
    ) {}

    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'purchase_order_id' => $this->purchaseOrderId,
            'invoice_number' => $this->invoiceNumber,
            'supplier_invoice_number' => $this->supplierInvoiceNumber,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'notes' => $this->notes,
            'attachment_path' => $this->attachmentPath,
            'reviewers' => $this->reviewers,
        ];
    }
}
