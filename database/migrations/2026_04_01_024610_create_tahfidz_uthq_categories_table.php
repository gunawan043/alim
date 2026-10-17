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
        Schema::create('tahfidz_uthq_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uthq_event_id');
            $table->string('name', 100)->comment('3 Juz, 5 Juz, 10 Juz, 15 Juz, 30 Juz');
            $table->tinyInteger('juz_count');
            $table->json('juz_detail')->nullable()->comment('Juz yang diujikan: [28,29,30]');
            $table->integer('max_participants')->nullable();
            $table->timestamps();
            $table->foreign('uthq_event_id')->references('id')->on('tahfidz_uthq_events')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_uthq_categories');
    }
};
