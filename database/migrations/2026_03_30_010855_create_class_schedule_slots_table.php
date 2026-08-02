<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedule_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->tinyInteger('slot_number')->comment('Jam ke-');
            $table->string('label', 20)->comment('Contoh: Jam 1, Istirahat, Sholat Dzuhur');
            $table->time('time_start');
            $table->time('time_end');
            $table->tinyInteger('is_break')->default(0)->comment('1 = istirahat, tidak ada mapel');
            $table->tinyInteger('day_of_week')->comment('1=Senin, 2=Selasa, ..., 6=Sabtu, 7=Minggu');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            // Satu sekolah tidak boleh punya slot jam yang sama di hari yang sama
            $table->unique(['school_id', 'day_of_week', 'slot_number'], 'unique_slot_per_school_day');
            $table->index(['school_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedule_slots');
    }
};
