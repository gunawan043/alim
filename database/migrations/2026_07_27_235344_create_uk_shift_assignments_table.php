<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uk_shift_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assigned_to_id');
            $table->uuid('created_by_id')->nullable();
            $table->date('shift_date');
            $table->enum('shift_type', ['pagi', 'siang', 'malam', 'full_day'])->default('pagi');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->unique(['shift_date', 'assigned_to_id']);
            $table->timestamps();

            $table->foreign('assigned_to_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['shift_date', 'shift_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uk_shift_assignments');
    }
};
