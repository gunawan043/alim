<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_medicine_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('inventory_id');
            $table->uuid('academic_year_id');
            $table->date('log_date');
            $table->time('time_given')->nullable();
            $table->decimal('quantity_given', 10, 2);
            $table->string('dosage', 191)->nullable()
                ->comment('Dosis yang diberikan, misal: 1 tablet, 5ml');
            $table->text('purpose')->nullable()
                ->comment('Indikasi/purpose pemberian obat');
            $table->uuid('administered_by');
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('inventory_id')->references('id')->on('student_medicine_inventory')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('administered_by')->references('id')->on('users');

            $table->index(['inventory_id', 'log_date']);
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_medicine_logs');
    }
};
