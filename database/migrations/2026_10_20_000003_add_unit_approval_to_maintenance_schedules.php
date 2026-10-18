<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_maintenance_schedules', 'unit_approval_status')) {
                $table->string('unit_approval_status', 30)->default('pending')->after('status')
                    ->comment('pending, approved, reschedule_requested, rejected');
            }
            if (! Schema::hasColumn('asset_maintenance_schedules', 'unit_approved_by')) {
                $table->uuid('unit_approved_by')->nullable()->after('unit_approval_status');
            }
            if (! Schema::hasColumn('asset_maintenance_schedules', 'unit_approved_at')) {
                $table->timestamp('unit_approved_at')->nullable()->after('unit_approved_by');
            }
            if (! Schema::hasColumn('asset_maintenance_schedules', 'unit_approval_note')) {
                $table->text('unit_approval_note')->nullable()->after('unit_approved_at');
            }
            if (! Schema::hasColumn('asset_maintenance_schedules', 'unit_proposed_date')) {
                $table->date('unit_proposed_date')->nullable()->after('unit_approval_note')
                    ->comment('Usulan tanggal dari satuan kerja');
            }
        });

        // Jadwal lama dianggap sudah disepakati agar tidak menggantung.
        DB::table('asset_maintenance_schedules')->update([
            'unit_approval_status' => 'approved',
            'unit_approved_at' => DB::raw('COALESCE(unit_approved_at, created_at)'),
        ]);

        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            $table->foreign('unit_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['work_unit_id', 'unit_approval_status'], 'maintenance_unit_approval_index');
        });
    }

    public function down(): void
    {
        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            $table->dropIndex('maintenance_unit_approval_index');
            $table->dropForeign(['unit_approved_by']);
            $table->dropColumn([
                'unit_approval_status', 'unit_approved_by', 'unit_approved_at',
                'unit_approval_note', 'unit_proposed_date',
            ]);
        });
    }
};
