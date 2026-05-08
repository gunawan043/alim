<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_maintenance_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            // Salah satu dari tiga ini diisi, sisanya NULL
            $table->uuid('asset_id')->nullable();
            $table->uuid('building_id')->nullable();
            $table->uuid('room_id')->nullable();
            $table->string('maintenance_type', 100)
                  ->comment('Misal: Servis AC, Kuras Bak Air, Cat Ulang, Kalibrasi Alat Lab');
            $table->enum('frequency', [
                'harian', 'mingguan', 'bulanan',
                'triwulan', 'semester', 'tahunan', 'sesuai_kebutuhan',
            ])->default('bulanan');
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date');
            $table->uuid('responsible_user_id')->nullable();
            $table->string('vendor_name', 191)->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->integer('reminder_days_before')->default(7)
                  ->comment('Kirim notifikasi N hari sebelum jadwal');
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('responsible_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(
                ['is_active', 'next_maintenance_date'],
                'idx_active_next_date'
            );

            $table->index(
                ['work_unit_id', 'frequency'],
                'idx_workunit_freq'
            );
        });
    }

    public function down(): void { Schema::dropIfExists('asset_maintenance_schedules'); }
};
