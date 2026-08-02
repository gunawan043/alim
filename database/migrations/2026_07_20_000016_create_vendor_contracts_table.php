<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 100)->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->char('user_id', 36);
            $table->string('title', 255);
            $table->text('scope')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('auto_renewal_date')->nullable();
            $table->enum('renewal_type', [
                'manual',
                'automatic',
                'none',
            ])->default('manual');
            $table->enum('status', [
                'draft',
                'active',
                'expiring_soon',
                'expired',
                'terminated',
                'suspended',
            ])->default('draft');
            $table->decimal('annual_value', 18, 2)->nullable();
            $table->decimal('monthly_value', 18, 2)->nullable();
            $table->json('slas')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->unsignedBigInteger('signed_by_vendor')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->unsignedBigInteger('signed_by_admin')->nullable();
            $table->timestamp('admin_signed_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index('status');
            $table->index('end_date');
        });

        // vendor_slas.contract_id FK is added here because vendor_slas is created
        // earlier in 2026_03_31_100002_vendor_master (before vendor_contracts exists).
        if (Schema::hasTable('vendor_slas')) {
            Schema::table('vendor_slas', function (Blueprint $table) {
                $table->foreign('contract_id')
                    ->references('id')
                    ->on('vendor_contracts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_slas')) {
            Schema::table('vendor_slas', function (Blueprint $table) {
                $table->dropForeign(['contract_id']);
            });
        }
        Schema::dropIfExists('vendor_contracts');
    }
};
