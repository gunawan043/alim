<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 13. create_tahfidz_tikror_assignments_table.php
// Penugasan tikror (kolom 1-10 pada Gambar 2).
// Bukan nilai, melainkan penugasan pengulangan mandiri per setoran.
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_tikror_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('setoran_id');
            $table->uuid('student_id');
            $table->tinyInteger('tikror_number')
                  ->comment('Urutan tikror ke-1 s/d ke-10 sesuai kolom waraqat');
            $table->date('assigned_date');
            $table->date('due_date')->nullable();
            $table->tinyInteger('is_completed')->default(0);
            $table->date('completed_date')->nullable();
            $table->uuid('verified_by')->nullable()->comment('Guru yang memverifikasi selesai');
            $table->timestamps();

            $table->foreign('setoran_id')->references('id')->on('tahfidz_setorans')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['setoran_id', 'tikror_number'], 'unique_tikror_per_setoran');
            $table->index(
                ['student_id', 'is_completed', 'due_date'],
                'idx_std_done_due'
            );
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_tikror_assignments'); }
};
