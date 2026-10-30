<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('asset_damage_reports')) {
            return;
        }

        if (Schema::hasColumn('asset_damage_reports', 'repair_request_id')) {
            if (Schema::getColumnType('asset_damage_reports', 'repair_request_id') !== 'bigint') {
                Schema::table('asset_damage_reports', fn (Blueprint $table) => $table->dropColumn('repair_request_id'));
            } else {
                return;
            }
        }

        Schema::table('asset_damage_reports', function (Blueprint $table) {
            $table->foreignId('repair_request_id')->nullable()->after('asset_id')
                ->constrained('repair_requests')->nullOnDelete();
        });
    }
    public function down(): void {
        if (Schema::hasTable('asset_damage_reports') && Schema::hasColumn('asset_damage_reports', 'repair_request_id')) {
            Schema::table('asset_damage_reports', fn (Blueprint $table) => $table->dropConstrainedForeignId('repair_request_id'));
        }
    }
};
