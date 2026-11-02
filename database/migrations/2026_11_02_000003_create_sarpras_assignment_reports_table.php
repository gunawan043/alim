<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan penugasan Sarpras (work order perbaikan/pemeliharaan).
 * Kebersihan sudah punya cleaning_reports; modul ini khusus penugasan
 * teknis sehingga hasil kerja, kendala, dan tindak lanjut tercatat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sarpras_assignment_reports')) {
            return;
        }

        Schema::create('sarpras_assignment_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // work_orders & repair_requests memakai primary key bigint.
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->unsignedBigInteger('repair_request_id')->nullable();
            $table->uuid('maintenance_schedule_id')->nullable();
            $table->uuid('asset_id')->nullable();
            $table->uuid('work_unit_id')->nullable();
            $table->uuid('school_id')->nullable();

            $table->uuid('reported_by');
            $table->date('report_date');

            // Isi laporan — sengaja singkat agar cepat diisi dari lapangan.
            $table->text('work_done');
            $table->text('obstacles')->nullable();
            $table->text('follow_up')->nullable();
            $table->string('parts_replaced', 191)->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('condition_after', 30)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->string('status', 20)->default('submitted'); // draft|submitted|verified
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();

            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['work_order_id']);
            $table->index(['repair_request_id']);
            $table->index(['status', 'report_date']);
            $table->index(['work_unit_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarpras_assignment_reports');
    }
};
