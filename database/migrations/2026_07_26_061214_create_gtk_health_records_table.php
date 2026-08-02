<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GTK Health Records — time-series health measurements for GTK (tenaga kependidikan).
     *
     * Each row is a single check-up event for a GTK staff member, capturing:
     *   - Vital signs (BP, pulse, temperature, etc.)
     *   - Anthropometry (height, weight, BMI, waist)
     *   - Lab results (cholesterol, blood sugar, uric acid, hemoglobin)
     *   - Organ function (liver, kidney)
     *   - Special checks (eye, lung peak flow)
     *   - Health summary (notes, complaints, recommendation)
     */
    public function up(): void
    {
        Schema::create('gtk_health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained();
            $table->date('check_date')->comment('Tanggal pemeriksaan');

            // ── Vitals ────────────────────────────────────────────────
            $table->string('blood_pressure')->nullable()->comment('Sistolik/Diastolik, e.g. 120/80');
            $table->unsignedSmallInteger('pulse')->nullable()->comment('BPM');
            $table->decimal('temperature', 4, 1)->nullable()->comment('Celsius');
            $table->decimal('respiration_rate', 4, 1)->nullable()->comment('Breaths per minute');
            $table->decimal('oxygen_saturation', 4, 1)->nullable()->comment('SpO2 %');

            // ── Anthropometry ─────────────────────────────────────────
            $table->decimal('height', 5, 2)->nullable()->comment('cm');
            $table->decimal('weight', 5, 2)->nullable()->comment('kg');
            $table->decimal('bmi', 5, 2)->nullable()->comment('Calculated BMI');
            $table->decimal('waist_circumference', 5, 2)->nullable()->comment('cm');

            // ── Lab Results ───────────────────────────────────────────
            $table->decimal('cholesterol_total', 6, 2)->nullable()->comment('mg/dL');
            $table->decimal('triglycerides', 6, 2)->nullable()->comment('mg/dL');
            $table->decimal('blood_sugar_fasting', 6, 2)->nullable()->comment('mg/dL, GDP');
            $table->decimal('blood_sugar_random', 6, 2)->nullable()->comment('mg/dL, GDS');
            $table->decimal('blood_sugar_postprandial', 6, 2)->nullable()->comment('mg/dL, 2 jam PP');
            $table->decimal('uric_acid', 5, 2)->nullable()->comment('mg/dL, Asam urat');
            $table->decimal('hemoglobin', 4, 1)->nullable()->comment('g/dL');

            // ── Organ Function ────────────────────────────────────────
            $table->decimal('sgot_ast', 6, 2)->nullable()->comment('U/L, fungsi hati');
            $table->decimal('sgpt_alt', 6, 2)->nullable()->comment('U/L, fungsi hati');
            $table->decimal('creatinine', 5, 2)->nullable()->comment('mg/dL, fungsi ginjal');
            $table->decimal('bun', 5, 2)->nullable()->comment('mg/dL, fungsi ginjal');

            // ── Special Checks ───────────────────────────────────────
            $table->string('right_eye_vision', 10)->nullable()->comment('Visus mata kanan, e.g. 6/6');
            $table->string('left_eye_vision', 10)->nullable()->comment('Visus mata kiri');
            $table->unsignedSmallInteger('peak_flow')->nullable()->comment('L/min, peak expiratory flow');
            $table->enum('smoking_status', ['tidak_pernah', 'mantan', 'aktif'])->nullable();
            $table->enum('physical_activity', ['jarang', 'sedang', 'sering'])->nullable();

            // ── Health Summary ───────────────────────────────────────
            $table->text('complaints')->nullable()->comment('Keluhan yang dirasakan saat MCU');
            $table->text('physical_examination')->nullable()->comment('Hasil pemeriksaan fisik');
            $table->text('diagnosis')->nullable()->comment('Diagnosa / ICD-10 code');
            $table->text('recommendation')->nullable()->comment('Saran medis / rujukan');
            $table->enum('fitness_status', ['sehat', 'sehat_dengan_catatan', 'belum_sehat'])->nullable();
            $table->boolean('referred_to_faskes')->default(false);
            $table->string('referral_reason')->nullable();

            // ── Audit ────────────────────────────────────────────────
            $table->foreignUuid('recorded_by')->nullable()->constrained('users');
            $table->string('source', 50)->default('mcu')->comment('mcu / mandiri / medical_check / klinik');
            $table->timestamps();

            $table->index(['user_id', 'check_date']);
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_health_records');
    }
};
