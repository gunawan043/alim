<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->enum('type', ['main', 'secondary', 'transit', 'vendor_consignment'])->default('main');
            $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->string('building_id')->nullable();
            $table->foreignUuid('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('code', 50);
            $table->string('name', 150)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code'], 'unique_warehouse_rack_code');
        });

        Schema::create('warehouse_bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('warehouse_racks')->onDelete('cascade');
            $table->string('code', 50);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['rack_id', 'code'], 'unique_rack_bin_code');
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // pcs, m, kg, l, box
            $table->string('name', 50);
            $table->string('symbol', 10)->nullable();
            $table->enum('category', ['count', 'length', 'weight', 'volume', 'time'])->default('count');
            $table->timestamps();
        });

        Schema::create('sparepart_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('sparepart_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('spareparts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number', 100)->unique();
            $table->string('name', 200);
            $table->string('slug', 200)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('sparepart_categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('primary_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained('warehouse_bins')->nullOnDelete();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('qr_path')->nullable();
            $table->decimal('stock', 14, 2)->default(0);
            $table->decimal('min_stock', 14, 2)->default(0);
            $table->decimal('max_stock', 14, 2)->default(0);
            $table->decimal('reorder_point', 14, 2)->default(0);
            $table->decimal('reorder_quantity', 14, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('average_cost', 18, 2)->default(0);
            $table->decimal('last_purchase_price', 18, 2)->nullable();
            $table->string('currency', 5)->default('IDR');
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('weight_kg', 10, 3)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('manufacturer_part', 100)->nullable();
            $table->boolean('is_hazardous')->default(false);
            $table->boolean('is_consumable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('lifetime_days')->nullable();
            $table->timestamps();
            $table->index('category_id');
            $table->index('warehouse_id');
        });

        Schema::create('sparepart_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('movement_code')->unique();
            $table->foreignId('sparepart_id')->constrained('spareparts')->onDelete('cascade');
            $table->enum('movement_type', ['receive', 'issue', 'transfer', 'return', 'adjustment', 'opname', 'reserved', 'unreserved'])->index();
            $table->decimal('quantity', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('from_bin_id')->nullable()->constrained('warehouse_bins')->nullOnDelete();
            $table->foreignId('to_bin_id')->nullable()->constrained('warehouse_bins')->nullOnDelete();
            $table->string('reference_type', 100)->nullable(); // work_order, repair_request
            $table->string('reference_id', 100)->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->useCurrent();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_immutable')->default(true);
            $table->timestamps();
            $table->index(['sparepart_id', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('sparepart_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sparepart_id')->constrained('spareparts')->onDelete('cascade');
            $table->string('reference_type', 100); // work_order, repair_request
            $table->string('reference_id', 100);
            $table->decimal('quantity', 14, 2);
            $table->decimal('consumed_quantity', 14, 2)->default(0);
            $table->foreignUuid('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'consumed', 'released', 'expired'])->default('active');
            $table->timestamps();
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'partial', 'received', 'cancelled', 'closed'])->default('draft');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('shipping', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->string('currency', 5)->default('IDR');
            $table->unsignedSmallInteger('payment_term_days')->default(30);
            $table->string('incoterms', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('vendor_id');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('sparepart_id')->nullable()->constrained('spareparts')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 14, 2);
            $table->decimal('received_quantity', 14, 2)->default(0);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('tax_pct', 5, 2)->default(0);
            $table->decimal('line_total', 18, 2);
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamps();
            $table->index('purchase_order_id');
        });

        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 100)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('work_order_ref')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->string('document_path')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('vendor_id');
        });

        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number', 50)->unique();
            $table->foreignUuid('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('vendor_warranty_id')->nullable()->constrained('vendor_warranties')->nullOnDelete();
            $table->text('defect_description');
            $table->date('claim_date');
            $table->date('resolution_date')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'in_progress', 'completed', 'rejected', 'cancelled'])->default('draft');
            $table->enum('outcome', ['repaired', 'replaced', 'refunded', 'void'])->nullable();
            $table->decimal('claimed_amount', 18, 2)->nullable();
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('vendor_invoices');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('sparepart_reservations');
        Schema::dropIfExists('sparepart_stock_movements');
        Schema::dropIfExists('spareparts');
        Schema::dropIfExists('sparepart_categories');
        Schema::dropIfExists('units');
        Schema::dropIfExists('warehouse_bins');
        Schema::dropIfExists('warehouse_racks');
        Schema::dropIfExists('warehouses');
    }
};