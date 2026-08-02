<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tahfidz_uthq_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uthq_event_id');
            $table->uuid('uthq_category_id');
            $table->uuid('student_id');
            $table->uuid('school_id')->nullable();
            $table->string('nomor_peserta', 30)->unique()
                ->comment('Generate otomatis, misal: UTHQ-2025-001');
            $table->json('juz_materi')->nullable();
            $table->date('registration_date');
            $table->enum('status', ['terdaftar', 'lolos_audisi', 'finalis', 'tidak_lolos', 'diskualifikasi'])->default('terdaftar');
            $table->timestamps();
            $table->foreign('uthq_event_id')->references('id')->on('tahfidz_uthq_events')->cascadeOnDelete();
            $table->foreign('uthq_category_id')->references('id')->on('tahfidz_uthq_categories')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->index(
                ['uthq_event_id', 'uthq_category_id', 'status'],
                'idx_uthq_evt_cat_stat'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_uthq_registrations');
    }
};
