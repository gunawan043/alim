<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('waste_facilities')) {
            Schema::create('waste_facilities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('facility_type', 40)->default('tps');
                $table->uuid('building_id')->nullable();
                $table->string('location')->nullable();
                $table->decimal('capacity_m3', 10, 2)->nullable();
                $table->decimal('capacity_kg', 12, 2)->nullable();
                $table->string('condition', 30)->default('baik');
                $table->uuid('pic_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
                $table->foreign('pic_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['facility_type', 'is_active']);
            });
        }

        if (Schema::hasTable('waste_logs') && ! Schema::hasColumn('waste_logs', 'facility_id')) {
            Schema::table('waste_logs', function (Blueprint $table) {
                $table->uuid('facility_id')->nullable()->after('source_id');
                $table->foreign('facility_id')->references('id')->on('waste_facilities')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('waste_logs') && Schema::hasColumn('waste_logs', 'facility_id')) {
            Schema::table('waste_logs', function (Blueprint $table) {
                $table->dropForeign(['facility_id']);
                $table->dropColumn('facility_id');
            });
        }

        Schema::dropIfExists('waste_facilities');
    }
};
