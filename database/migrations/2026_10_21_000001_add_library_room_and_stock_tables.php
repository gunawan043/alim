<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_units') && ! Schema::hasColumn('library_units', 'asset_room_id')) {
            Schema::table('library_units', function (Blueprint $table) {
                $table->uuid('asset_room_id')->nullable()->after('school_id');
                $table->index('asset_room_id');
            });
        }

        if (Schema::hasTable('room_bookings')) {
            Schema::table('room_bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('room_bookings', 'library_unit_id')) {
                    $table->uuid('library_unit_id')->nullable()->after('room_id');
                    $table->index('library_unit_id');
                }
                if (! Schema::hasColumn('room_bookings', 'booking_source')) {
                    $table->string('booking_source', 20)->default('request')->after('participants_count');
                }
            });
        }

        if (! Schema::hasTable('library_stock_opnames')) {
            Schema::create('library_stock_opnames', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->date('session_date');
                $table->string('status', 20)->default('draft');
                $table->uuid('coordinator_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->foreign('coordinator_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['library_unit_id', 'status']);
            });
        }

        if (! Schema::hasTable('library_stock_opname_items')) {
            Schema::create('library_stock_opname_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_stock_opname_id')->constrained('library_stock_opnames')->cascadeOnDelete();
                $table->foreignUuid('library_item_id')->constrained('library_items')->cascadeOnDelete();
                $table->string('finding', 30)->default('pending');
                $table->string('system_status', 30)->nullable();
                $table->string('physical_location')->nullable();
                $table->string('condition', 30)->nullable();
                $table->text('notes')->nullable();
                $table->uuid('checked_by')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
                $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(['library_stock_opname_id', 'library_item_id'], 'library_stock_item_unique');
                $table->index(['library_stock_opname_id', 'finding']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('library_stock_opname_items');
        Schema::dropIfExists('library_stock_opnames');
        if (Schema::hasTable('room_bookings')) {
            Schema::table('room_bookings', function (Blueprint $table) {
                if (Schema::hasColumn('room_bookings', 'booking_source')) $table->dropColumn('booking_source');
                if (Schema::hasColumn('room_bookings', 'library_unit_id')) $table->dropColumn('library_unit_id');
            });
        }
        if (Schema::hasTable('library_units') && Schema::hasColumn('library_units', 'asset_room_id')) {
            Schema::table('library_units', fn (Blueprint $table) => $table->dropColumn('asset_room_id'));
        }
    }
};
