<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sarpras_sla_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_type', 50)->index(); // work_order, loan, maintenance
            $table->enum('priority', ['low', 'medium', 'high', 'urgent', 'critical'])->default('medium');
            $table->unsignedSmallInteger('response_minutes')->nullable();
            $table->unsignedInteger('resolution_minutes')->default(1440);
            $table->unsignedInteger('escalation_minutes')->nullable();
            $table->json('escalation_chain')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['workflow_type', 'priority'], 'unique_sla_workflow_priority');
        });

        Schema::create('sarpras_sla_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_type', 50);
            $table->morphs('entity'); // entity_type + entity_id
            $table->enum('priority', ['low', 'medium', 'high', 'urgent', 'critical'])->default('medium');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('accumulated_seconds')->default(0);
            $table->enum('status', ['on_track', 'warning', 'overdue', 'escalated', 'completed'])->default('on_track');
            $table->unsignedTinyInteger('escalation_level')->default(0);
            $table->timestamps();
        });

        Schema::create('technician_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('category_slug', 100);
            $table->enum('proficiency', ['novice', 'intermediate', 'expert', 'master'])->default('intermediate');
            $table->boolean('is_certified')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'category_slug'], 'unique_technician_skill');
        });

        Schema::create('technician_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->enum('status', ['available', 'busy', 'offline', 'leave'])->default('available');
            $table->unsignedInteger('current_active_orders')->default(0);
            $table->unsignedInteger('max_concurrent_orders')->default(3);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('asset_id')->unique()->constrained('assets')->onDelete('cascade');
            $table->unsignedTinyInteger('health_score');
            $table->char('grade', 1)->nullable();
            $table->unsignedInteger('repair_count')->default(0);
            $table->unsignedBigInteger('lifetime_repair_cost')->default(0);
            $table->unsignedInteger('total_downtime_minutes')->default(0);
            $table->unsignedInteger('maintenance_overdue_count')->default(0);
            $table->unsignedInteger('audit_failures_count')->default(0);
            $table->unsignedInteger('age_years')->default(1);
            $table->timestamp('last_computed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_criticalities', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('asset_id')->unique()->constrained('assets')->onDelete('cascade');
            $table->enum('criticality', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->unsignedTinyInteger('score');
            $table->json('factors')->nullable();
            $table->timestamps();
        });

        // Seed SLA definitions
        $defaults = [
            ['work_order', 'critical', 15, 120, 60, ['1', '2', '3']],
            ['work_order', 'high', 30, 240, 120, ['1', '2']],
            ['work_order', 'medium', 60, 1440, null, ['1']],
            ['work_order', 'low', 120, 2880, null, []],
            ['loan', 'medium', 30, 4320, null, ['1']],
            ['maintenance', 'medium', null, 1440, 120, ['1', '2']],
            ['maintenance', 'high', null, 720, 60, ['1', '2', '3']],
        ];

        foreach ($defaults as $d) {
            \Illuminate\Support\Facades\DB::table('sarpras_sla_definitions')->insert([
                'workflow_type' => $d[0],
                'priority' => $d[1],
                'response_minutes' => $d[2] ?? null,
                'resolution_minutes' => $d[3],
                'escalation_minutes' => $d[4] ?? null,
                'escalation_chain' => json_encode($d[5]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_criticalities');
        Schema::dropIfExists('asset_health_metrics');
        Schema::dropIfExists('technician_availabilities');
        Schema::dropIfExists('technician_skills');
        Schema::dropIfExists('sarpras_sla_trackers');
        Schema::dropIfExists('sarpras_sla_definitions');
    }
};