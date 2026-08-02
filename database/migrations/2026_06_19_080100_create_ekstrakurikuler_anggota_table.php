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
        Schema::create('ekstrakurikuler_anggota', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ekstrakurikuler_id')->constrained('ekstrakurikuler')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('tanggal_bergabung')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->string('status')->default('aktif'); // aktif | keluar | lulus
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ekstrakurikuler_id', 'status'], 'idx_ekstra_anggota_status');
            $table->index(['student_id', 'status'], 'idx_ekstra_anggota_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ekstrakurikuler_anggota');
    }
};
