<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal_clone_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('soal_asli_id');
            $table->uuid('soal_clone_id');
            $table->uuid('cloned_by');
            $table->timestamp('cloned_at')->useCurrent();
            $table->uuid('from_school_id')->nullable();
            $table->uuid('to_school_id')->nullable();
            $table->enum('clone_type', ['fork', 'adapt', 'verbatim'])->default('verbatim');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('soal_asli_id')->references('id')->on('soal')->cascadeOnDelete();
            $table->foreign('soal_clone_id')->references('id')->on('soal')->cascadeOnDelete();
            $table->foreign('cloned_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('from_school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('to_school_id')->references('id')->on('schools')->nullOnDelete();

            $table->index('soal_asli_id', 'soal_clone_log_asli_idx');
            $table->index('soal_clone_id', 'soal_clone_log_clone_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal_clone_log');
    }
};
