<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('uks_patients')->cascadeOnDelete();
            $table->foreignUuid('treatment_id')->nullable()->constrained('uks_treatments')->nullOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('prescribed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('prescription_no', 40)->nullable();
            $table->timestamp('prescribed_at')->nullable();
            $table->string('status', 20)->default('baru');
            $table->text('notes')->nullable();

            $table->foreignUuid('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispensed_at')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status'], 'uks_prescriptions_patient_status_idx');
            $table->index(['school_id', 'prescribed_at'], 'uks_prescriptions_school_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_prescriptions');
    }
};
