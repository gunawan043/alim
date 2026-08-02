<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students');
            $table->foreignUuid('school_id')->nullable()->constrained();
            $table->foreignUuid('academic_year_id')->nullable()->constrained();
            $table->foreignUuid('dormitory_id')->nullable()->constrained();

            // ── Patient registration info ────────────────────────────
            $table->enum('patient_type', ['rawat', 'pulang', 'balik'])->default('rawat');
            $table->text('chief_complaint')->nullable();       // Keluhan utama
            $table->json('symptoms')->nullable();              // Gejala
            $table->json('vitals')->nullable();                // BP, temp, pulse, height, weight, BMI
            $table->text('diagnosis')->nullable();              // Diagnosis UKS
            $table->text('treatment')->nullable();              // Pengobatan / perlakuan
            $table->string('medicine_given')->nullable();       // Nama obat yang diberikan
            $table->text('medication_details')->nullable();     // Detail pemberian obat (dosis, frekuensi, rute)
            $table->boolean('referred_to_faskes')->default(false);
            $table->string('referral_reason')->nullable();

            // ── Hospital-style bed tracking ────────────────────────
            $table->string('bed_number')->nullable();          // Nomor ranjang/tempat tidur di UKS
            $table->boolean('in_bed')->default(false);          // Apakah sedang berada di ranjang
            $table->timestamp('taken_bed_at')->nullable();      // Waktu naik ranjang
            $table->timestamp('left_bed_at')->nullable();       // Waktu turun ranjang

            // ── Status tracking ────────────────────────────────────
            $table->enum('status', ['aktif', 'selesai', 'dirujuk'])->default('aktif');
            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('discharged_at')->nullable();

            // ── Audit ──────────────────────────────────────────────
            $table->foreignUuid('admitted_by')->nullable()->constrained('users');
            $table->foreignUuid('discharged_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_patients');
    }
};
