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
        Schema::create('pekan_efektif', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester')->default(1); // 1 | 2
            $table->unsignedTinyInteger('minggu_ke'); // 1..19
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('jenis')->default('efektif');
            // efektif | libur | ujian | kegiatan_sekolah | lainnya
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['school_id', 'academic_year_id', 'semester', 'minggu_ke'],
                'uq_pe_unique_week'
            );
            $table->index(['school_id', 'academic_year_id', 'semester'], 'idx_pe_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pekan_efektif');
    }
};
