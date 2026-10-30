<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_schedule_availabilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('teacher_id')->constrained('users')->cascadeOnDelete();

            // JSON: { "1": [1,2,3], "2": [1,2] } — hari => slot yang tersedia.
            // Null/kosongnya baris berarti guru tersedia pada semua slot.
            $table->json('available_slots')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'teacher_id'], 'uk_guru_availability_school');
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_schedule_availabilities');
    }
};
