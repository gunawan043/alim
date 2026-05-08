<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('resident_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id');
            $table->date('activity_date');
            $table->enum('session', ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam']);
            $table->json('data')->nullable()->comment('Data aktivitas mengikuti template aktif saat itu');
            $table->text('notes')->nullable();
            $table->boolean('notify_parent')->default(false);
            $table->uuid('recorded_by');
            $table->timestamps();

            $table->foreign('resident_id')->references('id')->on('dormitory_residents')->onDelete('cascade');
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->onDelete('cascade');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users');

            $table->unique(
                ['resident_id', 'activity_date', 'session'],
                'unique_activity_log_per_session'
            );
            $table->index(['dormitory_id', 'activity_date']);
            $table->index(['resident_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_activity_logs');
    }
};