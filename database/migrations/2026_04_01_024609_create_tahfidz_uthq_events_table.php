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
        Schema::create('tahfidz_uthq_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->string('name', 191);
            $table->uuid('academic_year_id');
            $table->date('event_date_start');
            $table->date('event_date_end');
            $table->string('location', 191)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'pendaftaran', 'audisi', 'final', 'selesai'])->default('draft');
            $table->uuid('created_by');
            $table->timestamps();
            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_uthq_events');
    }
};
