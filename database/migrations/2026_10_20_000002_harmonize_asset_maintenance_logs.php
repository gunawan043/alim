<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_maintenance_logs', 'maintenance_type')) {
                $table->string('maintenance_type', 100)->nullable()->after('log_type');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'performed_by')) {
                $table->uuid('performed_by')->nullable()->after('reported_by');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'actual_cost')) {
                $table->decimal('actual_cost', 15, 2)->nullable()->after('cost');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'work_description')) {
                $table->text('work_description')->nullable()->after('description');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'parts_replaced')) {
                $table->text('parts_replaced')->nullable()->after('work_description')
                    ->comment('Suku cadang/perangkat yang diganti');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'next_action_needed')) {
                $table->text('next_action_needed')->nullable()->after('parts_replaced');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'created_by')) {
                $table->uuid('created_by')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('asset_maintenance_logs', 'next_due_date')) {
                $table->date('next_due_date')->nullable()->after('next_maintenance_date');
            }
        });

        // Salin data lama ke kolom kanonik agar riwayat yang sudah ada tidak hilang.
        DB::table('asset_maintenance_logs')->update([
            'maintenance_type' => DB::raw("COALESCE(NULLIF(maintenance_type, ''), log_type, 'korektif')"),
            'performed_by' => DB::raw('COALESCE(performed_by, handled_by)'),
            'actual_cost' => DB::raw('COALESCE(actual_cost, cost)'),
            'work_description' => DB::raw('COALESCE(work_description, description)'),
            'created_by' => DB::raw('COALESCE(created_by, handled_by)'),
            'next_due_date' => DB::raw('COALESCE(next_due_date, next_maintenance_date)'),
        ]);

        Schema::table('asset_maintenance_logs', function (Blueprint $table) {
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['asset_id', 'maintenance_date'], 'asset_logs_asset_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('asset_maintenance_logs', function (Blueprint $table) {
            $table->dropIndex('asset_logs_asset_date_index');
            $table->dropForeign(['performed_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'maintenance_type', 'performed_by', 'actual_cost', 'work_description',
                'parts_replaced', 'next_action_needed', 'created_by', 'next_due_date',
            ]);
        });
    }
};
