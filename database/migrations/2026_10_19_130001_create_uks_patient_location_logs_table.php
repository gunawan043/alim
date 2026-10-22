<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_patient_location_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('uks_patients')->cascadeOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('bed_id')->nullable()->constrained('uks_beds')->nullOnDelete();

            $table->string('from_location', 30)->nullable();
            $table->string('to_location', 30);
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->index();

            $table->timestamps();

            $table->index(['patient_id', 'recorded_at'], 'uks_location_logs_patient_time_idx');
            $table->index(['to_location', 'recorded_at'], 'uks_location_logs_location_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_patient_location_logs');
    }
};
