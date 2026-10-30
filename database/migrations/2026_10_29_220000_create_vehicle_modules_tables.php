<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Transportasi URT (menutup menu "Soon" di sidebar Unit Rumah Tangga).
 *
 *  - vehicles              : sudah ada (2026_10_08_220000_create_gap_modules_tables)
 *  - vehicle_trips         → Jadwal Perjalanan
 *  - vehicle_fuel_logs     → Log BBM
 *  - vehicle_service_logs  → Servis Rutin
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_trips')) {
            Schema::create('vehicle_trips', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('vehicle_id');
                $table->uuid('driver_id')->nullable();
                $table->string('destination', 191);
                $table->string('purpose', 191)->nullable();
                $table->unsignedInteger('passengers')->default(0);
                $table->timestamp('departure_at')->nullable();
                $table->timestamp('return_at')->nullable();
                $table->unsignedInteger('odometer_start')->nullable();
                $table->unsignedInteger('odometer_end')->nullable();
                $table->string('status', 20)->default('dijadwalkan')->comment('dijadwalkan, berangkat, selesai, batal');
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
                $table->index(['school_id', 'departure_at']);
            });
        }

        if (! Schema::hasTable('vehicle_fuel_logs')) {
            Schema::create('vehicle_fuel_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('vehicle_id');
                $table->date('fuel_date');
                $table->string('fuel_type', 20)->default('solar')->comment('bensin, solar, pertalite, pertamax');
                $table->decimal('liters', 8, 2);
                $table->decimal('cost', 12, 2)->default(0);
                $table->unsignedInteger('odometer')->nullable();
                $table->string('receipt_no', 100)->nullable();
                $table->uuid('recorded_by');
                $table->timestamps();

                $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
                $table->index(['vehicle_id', 'fuel_date']);
            });
        }

        if (! Schema::hasTable('vehicle_service_logs')) {
            Schema::create('vehicle_service_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('school_id');
                $table->uuid('vehicle_id');
                $table->string('service_type', 40)->comment('rutin, perbaikan, ganti_oli, tune_up, lainnya');
                $table->date('service_date');
                $table->unsignedInteger('odometer')->nullable();
                $table->decimal('cost', 12, 2)->default(0);
                $table->string('vendor_name', 191)->nullable();
                $table->text('items_replaced')->nullable();
                $table->date('next_service_date')->nullable();
                $table->string('status', 20)->default('selesai')->comment('terjadwal, berjalan, selesai');
                $table->uuid('recorded_by');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
                $table->index(['vehicle_id', 'service_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_logs');
        Schema::dropIfExists('vehicle_fuel_logs');
        Schema::dropIfExists('vehicle_trips');
    }
};
