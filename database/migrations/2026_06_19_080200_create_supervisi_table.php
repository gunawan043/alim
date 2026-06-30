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
        Schema::create('supervisi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('gtk_id')->nullable()->constrained('gtk_profiles')->nullOnDelete();
            $table->foreignUuid('observer_id')->nullable()->constrained('gtk_profiles')->nullOnDelete();
            $table->string('gtk_name')->nullable();
            $table->string('observer_name')->nullable();
            $table->unsignedTinyInteger('semester')->default(1); // 1 | 2
            $table->string('mata_pelajaran')->nullable();
            $table->date('tanggal_supervisi');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('jenis_supervisi')->default('proses_pembelajaran');
            // perangkat_pembelajaran | proses_pembelajaran | penilaian | lainnya
            $table->text('tujuan')->nullable();
            $table->text('catatan_temuan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->string('status')->default('terjadwal');
            // terjadwal | berlangsung | selesai | dibatalkan
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'academic_year_id', 'status'], 'idx_sup_scope_status');
            $table->index(['school_id', 'tanggal_supervisi'], 'idx_sup_scope_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisi');
    }
};