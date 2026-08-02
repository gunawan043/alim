<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 50)->unique();
            $table->string('name', 200);
            $table->string('legal_name', 255)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('vendor_categories')->nullOnDelete();
            $table->enum('vendor_type', ['individual', 'company', 'government', 'cooperative'])->default('company');
            $table->enum('status', ['active', 'suspended', 'blacklist', 'inactive'])->default('active');
            $table->string('phone', 30)->nullable();
            $table->string('phone_alt', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->decimal('total_employees', 12, 0)->nullable();
            $table->decimal('rating_avg', 4, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->enum('risk_classification', ['low', 'medium', 'high'])->default('medium');
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->unsignedSmallInteger('payment_term_days')->default(30);
            $table->string('preferred_currency', 5)->default('IDR');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
            $table->index('category_id');
        });

        Schema::create('vendor_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('position', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->enum('contact_type', ['primary', 'billing', 'technical', 'emergency', 'sales'])->default('primary');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index('vendor_id');
        });

        Schema::create('vendor_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->enum('address_type', ['head_office', 'branch', 'warehouse', 'billing'])->default('head_office');
            $table->string('label', 100)->nullable();
            $table->text('street_address');
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country', 50)->default('Indonesia');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index('vendor_id');
        });

        Schema::create('vendor_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('bank_name', 100);
            $table->string('bank_branch', 100)->nullable();
            $table->string('account_number', 50);
            $table->string('account_holder', 200);
            $table->string('swift_code', 20)->nullable();
            $table->enum('currency', ['IDR', 'USD', 'EUR', 'JPY'])->default('IDR');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index('vendor_id');
        });

        Schema::create('vendor_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('npwp', 30)->nullable();
            $table->string('pkp_status', 20)->default('non_pkp'); // pkp, non_pkp
            $table->string('pkp_number', 50)->nullable();
            $table->string('tax_office', 150)->nullable();
            $table->string('tax_attachment_path')->nullable();
            $table->date('tax_registered_at')->nullable();
            $table->timestamps();
        });

        // vendor_contracts is created by 2026_07_20_000016_create_vendor_contracts_table.php
        // (the canonical schema with renewal_type, slas JSON, dual-signature, etc.).
        // This migration predates the vendor PO workflow work and must defer to it.

        Schema::create('vendor_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('warranty_number', 100)->nullable()->unique();
            $table->string('scope', 200)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('coverage_type', ['full', 'parts_only', 'labor_only', 'limited'])->default('full');
            $table->text('terms')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('status', ['active', 'expired', 'claimed', 'void'])->default('active');
            $table->timestamps();
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('vendor_slas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            // contract_id FK to vendor_contracts is added later by
            // 2026_07_20_000016 (vendor_contracts doesn't exist yet at this point).
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('workflow_type', 50); // repair, supply, emergency
            $table->unsignedSmallInteger('response_minutes')->default(60);
            $table->unsignedSmallInteger('resolution_minutes')->default(1440);
            $table->decimal('penalty_per_day', 18, 2)->nullable();
            $table->decimal('bonus_target_completion_pct', 5, 2)->default(95);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamps();
        });

        // vendor_documents is created by 2026_07_20_000017_create_vendor_documents_table.php
        // (the canonical schema with user_id, vendor_id FK, attachment_path, etc.).
        // This migration predates the vendor PO workflow work and must defer to it.

        Schema::create('vendor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->foreignUuid('rated_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_score'); // 1..5
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->unsignedTinyInteger('timeliness_score')->nullable();
            $table->unsignedTinyInteger('price_score')->nullable();
            $table->unsignedTinyInteger('communication_score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->index('vendor_id');
        });

        Schema::create('vendor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_orders', 12, 0)->default(0);
            $table->decimal('completed_orders', 12, 0)->default(0);
            $table->decimal('on_time_pct', 5, 2)->default(0);
            $table->decimal('quality_avg', 4, 2)->default(0);
            $table->decimal('response_time_avg_minutes', 12, 2)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->decimal('penalty_amount', 18, 2)->default(0);
            $table->enum('grade', ['A', 'B', 'C', 'D', 'E'])->default('B');
            $table->boolean('blacklist_recommendation')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['vendor_id', 'period_start', 'period_end'], 'unique_vendor_period');
        });

        Schema::create('vendor_performance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->date('snapshot_date');
            $table->decimal('rating_avg', 4, 2);
            $table->unsignedInteger('rating_count');
            $table->unsignedInteger('active_orders');
            $table->decimal('on_time_pct', 5, 2);
            $table->decimal('total_value_ytd', 18, 2);
            $table->timestamps();
            $table->index(['vendor_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_performance_history');
        Schema::dropIfExists('vendor_evaluations');
        Schema::dropIfExists('vendor_ratings');
        Schema::dropIfExists('vendor_slas');
        Schema::dropIfExists('vendor_warranties');
        Schema::dropIfExists('vendor_taxes');
        Schema::dropIfExists('vendor_banks');
        Schema::dropIfExists('vendor_addresses');
        Schema::dropIfExists('vendor_contacts');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('vendor_categories');
    }
};
