<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_program_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->boolean('is_done')->default(false);
            $table->uuid('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('cleaning_programs')->cascadeOnDelete();
            $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['program_id', 'year', 'month'], 'cleaning_program_check_unique');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaning_program_checks');
    }
};
