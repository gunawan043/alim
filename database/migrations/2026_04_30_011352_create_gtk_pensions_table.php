<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_pensions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->date('planned_pension_date')->nullable()->comment('Tanggal pensiun rencana (TMT)');
            $table->enum('pension_type', ['normal', 'dini', 'cacat', 'janda'])->default('normal')->comment('Jenis pensiun');
            $table->string('pension_letter_no', 100)->nullable()->comment('Nomor SK Pensiun');
            $table->date('pension_letter_date')->nullable()->comment('Tanggal SK Pensiun');
            $table->enum('pension_status', ['draft', 'pending', 'approved', 'completed', 'cancelled'])->default('draft')->comment('Status proses pensiun');
            $table->decimal('benefit_amount', 15, 2)->nullable()->comment('Besaran dana pensions (Rp)');
            $table->string('benefit_notes', 255)->nullable()->comment('Catatan benefit');
            $table->text('notes')->nullable()->comment('Catatan umum proses pensiun');
            $table->uuid('processed_by')->nullable()->comment('User ID yang memproses');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('pension_status');
            $table->index('planned_pension_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_pensions');
    }
};
