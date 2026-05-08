<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_recruitments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_unit_id')->constrained('work_units')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->string('jabatan');
            $table->unsignedTinyInteger('kebutuhan');
            $table->text('kualifikasi');
            $table->date('tanggal_dibutuhkan');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_recruitments');
    }
};