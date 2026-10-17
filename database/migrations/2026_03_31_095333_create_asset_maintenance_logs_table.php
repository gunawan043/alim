<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('schedule_id')->nullable()
                ->comment('NULL jika laporan kerusakan mendadak tanpa jadwal');
            // Salah satu dari tiga ini diisi
            $table->uuid('asset_id')->nullable();
            $table->uuid('building_id')->nullable();
            $table->uuid('room_id')->nullable();
            $table->date('maintenance_date');
            $table->enum('log_type', ['preventif', 'korektif', 'inspeksi', 'penghapusan'])
                ->default('korektif');
            $table->uuid('reported_by')->nullable()
                ->comment('Yang melaporkan kerusakan');
            $table->uuid('handled_by')->nullable()
                ->comment('Yang menangani perbaikan');
            $table->string('vendor_name', 191)->nullable();
            $table->text('description')
                ->comment('Deskripsi kerusakan atau pekerjaan pemeliharaan');
            $table->enum('condition_before', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat',
            ])->nullable();
            $table->enum('condition_after', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'dihapus',
            ])->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('cost_source', 100)->nullable()
                ->comment('BOS, Dana Ponpes, Donasi, Asuransi, dll');
            $table->decimal('duration_hours', 5, 1)->nullable();
            $table->enum('status', [
                'dilaporkan', 'diproses', 'selesai', 'ditunda', 'tidak_dapat_diperbaiki',
            ])->default('dilaporkan');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('before_photo_path', 255)->nullable();
            $table->string('after_photo_path', 255)->nullable();
            $table->string('document_path', 255)->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('schedule_id')->references('id')->on('asset_maintenance_schedules')->nullOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('handled_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'maintenance_date']);
            $table->index(['asset_id', 'log_type']);
            $table->index(['work_unit_id', 'log_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_logs');
    }
};
