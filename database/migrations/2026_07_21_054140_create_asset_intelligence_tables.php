<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_cost_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_id');
            $table->decimal('purchase_cost', 14, 2)->default(0);
            $table->decimal('repair_cost', 14, 2)->default(0);
            $table->decimal('maintenance_cost', 14, 2)->default(0);
            $table->decimal('sparepart_cost', 14, 2)->default(0);
            $table->decimal('operational_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('computed_at');
        });

        Schema::create('asset_recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_id');
            $table->string('recommendation'); // GOOD|MONITOR|REPAIR|REPLACE|CRITICAL
            $table->unsignedTinyInteger('score'); // 0-100 (higher = healthier)
            $table->decimal('repair_cost_ratio', 6, 3)->default(0); // repair_cost / replacement_value
            $table->decimal('estimated_repair_cost', 14, 2)->default(0);
            $table->decimal('replacement_value', 14, 2)->default(0);
            $table->unsignedInteger('age_years')->default(0);
            $table->unsignedInteger('damage_count')->default(0);
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->unsignedTinyInteger('availability_pct')->default(100);
            $table->unsignedTinyInteger('criticality')->default(1); // 1..5
            $table->unsignedTinyInteger('health_score')->default(100);
            $table->json('factor_breakdown')->nullable();
            $table->json('rationale')->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('recommendation');
        });

        Schema::create('asset_predictive_maintenance', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_id');
            $table->string('status')->default('scheduled'); // scheduled|overdue|completed|cancelled
            $table->date('maintenance_due_date')->nullable();
            $table->decimal('estimated_cost', 14, 2)->default(0);
            $table->string('priority')->default('normal'); // low|normal|high|critical
            $table->unsignedTinyInteger('confidence_score')->default(0); // 0-100
            $table->json('input_metrics')->nullable();
            $table->json('predicted_actions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('maintenance_due_date');
            $table->index('priority');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_predictive_maintenance');
        Schema::dropIfExists('asset_recommendations');
        Schema::dropIfExists('asset_cost_snapshots');
    }
};
