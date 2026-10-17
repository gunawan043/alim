<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('primary_vendor_id')->nullable()->after('asset_code')->constrained('vendors')->nullOnDelete();
            $table->string('repair_vendor', 200)->nullable()->after('work_unit_id');
            $table->decimal('total_repair_cost', 18, 2)->default(0)->after('current_value');
            $table->unsignedInteger('total_repair_count')->default(0)->after('total_repair_cost');
            $table->decimal('lifecycle_cost', 18, 2)->default(0)->after('total_repair_count');
            $table->unsignedInteger('remaining_useful_life_days')->nullable()->after('lifecycle_cost');
            $table->string('disposal_method', 50)->nullable()->default(null)->after('remaining_useful_life_days');
            $table->date('disposal_date')->nullable()->default(null)->after('disposal_method');
            $table->decimal('disposal_value', 18, 2)->nullable()->default(null)->after('disposal_date');
            $table->text('disposal_reason')->nullable()->default(null)->after('disposal_value');

            // Financial
            $table->string('cost_center', 100)->nullable()->after('asset_code');
            $table->string('budget_code', 100)->nullable()->after('cost_center');
            $table->enum('capex_opex', ['capex', 'opex'])->default('capex')->after('budget_code');
            $table->decimal('annual_maintenance_cost', 18, 2)->default(0)->after('capex_opex');
        });

        Schema::create('asset_vendor_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->enum('relationship_type', ['supplier', 'service_provider', 'warranty', 'sparepart'])->default('supplier');
            $table->date('start_date')->useCurrent();
            $table->date('end_date')->nullable();
            $table->text('context')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_spares_history', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('sparepart_id')->constrained('spareparts')->onDelete('cascade');
            $table->decimal('quantity_used', 14, 2);
            $table->string('reference_type', 100)->nullable(); // repair_request, work_order
            $table->string('reference_id', 100)->nullable();
            $table->timestamp('installed_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_spares_history');
        Schema::dropIfExists('asset_vendor_histories');
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['primary_vendor_id']);
            $table->dropColumn([
                'primary_vendor_id', 'repair_vendor', 'total_repair_cost',
                'total_repair_count', 'lifecycle_cost', 'remaining_useful_life_days',
                'disposal_method', 'disposal_date', 'disposal_value', 'disposal_reason',
                'cost_center', 'budget_code', 'capex_opex', 'annual_maintenance_cost',
            ]);
        });
    }
};
