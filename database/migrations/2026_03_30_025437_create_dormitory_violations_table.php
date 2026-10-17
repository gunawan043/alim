<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('room_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id');
            $table->uuid('recorded_by');
            $table->date('violation_date');
            $table->enum('violation_category', ['ringan', 'sedang', 'berat']);
            $table->string('violation_type', 100)->comment('Jenis pelanggaran spesifik');
            $table->text('description')->nullable();
            $table->integer('points')->default(0)->comment('Poin sanksi');
            $table->text('action_taken')->nullable();
            $table->text('follow_up')->nullable();
            $table->uuid('witness_id')->nullable();
            $table->timestamp('parent_notified_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->foreign('witness_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['student_id', 'academic_year_id']);
            $table->index(['dormitory_id', 'violation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_violations');
    }
};
