<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_health_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();

            // Basic health information
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', 'tidak_diketahui'])->nullable()->default('tidak_diketahui');
            $table->string('tekanan_darah', 10)->nullable()->comment('Pressure blood, e.g., "120/80"');
            $table->decimal('tinggi_badan', 5, 2)->nullable()->comment('Height in cm');
            $table->decimal('berat_badan', 5, 2)->nullable()->comment('Weight in kg');
            $table->string('lingkar_kepala', 10)->nullable()->comment('Head circumference in cm');
            $table->text('riwayat_penyakit')->nullable()->comment('Medical history');
            $table->text('alergi')->nullable()->comment('Allergies');
            $table->text('p3k')->nullable()->comment('First aid information');
            $table->text('keluhan_yang_dialami')->nullable()->comment('Symptoms experienced');

            // Additional vitals
            $table->integer('pulse')->nullable()->comment('Heart rate in bpm');
            $table->decimal('temperature', 5, 2)->nullable()->comment('Body temperature in °C');
            $table->decimal('waist_circumference', 5, 2)->nullable()->comment('Waist circumference in cm');

            // Lab baseline
            $table->decimal('cholesterol_total', 5, 2)->nullable()->comment('Total cholesterol in mg/dL');
            $table->decimal('triglycerides', 5, 2)->nullable()->comment('Triglycerides in mg/dL');
            $table->decimal('blood_sugar_fasting', 5, 2)->nullable()->comment('Fasting blood sugar in mg/dL');
            $table->decimal('uric_acid', 5, 2)->nullable()->comment('Uric acid level in mg/dL');
            $table->decimal('hemoglobin', 5, 2)->nullable()->comment('Hemoglobin level in g/dL');

            // Lifestyle & history
            $table->text('smoking_status')->nullable()->comment('Smoking status: never, former, current');
            $table->text('medical_history')->nullable()->comment('Detailed medical history');
            $table->text('ongoing_medication')->nullable()->comment('Current medications');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_health_data');
    }
};
