<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_health_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->unique()->comment('1:1 dengan tabel students');
            $table->uuid('school_id');
            $table->enum('blood_type', ['A', 'B', 'AB', 'O', 'tidak_diketahui'])
                ->default('tidak_diketahui');
            $table->integer('height_cm')->nullable()->comment('Tinggi badan dalam cm');
            $table->integer('weight_kg')->nullable()->comment('Berat badan dalam kg');
            $table->decimal('bmi', 5, 2)->nullable()->comment('Body Mass Index, dihitung otomatis');
            $table->text('allergies')->nullable()
                ->comment('Daftar alergi: makanan, obat, lingkungan, dll');
            $table->text('chronic_diseases')->nullable()
                ->comment('Penyakit bawaan / riwayat penyakit kronis');
            $table->text('current_medications')->nullable()
                ->comment('Obat yang rutin dikonsumsi beserta dosisnya');
            $table->text('emergency_notes')->nullable()
                ->comment('Catatan penting untuk tim medis saat darurat');
            $table->string('bpjs_number', 30)->nullable();
            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_number', 50)->nullable();
            $table->date('last_physical_exam_date')->nullable()
                ->comment('Tanggal pemeriksaan fisik terakhir');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_records');
    }
};
