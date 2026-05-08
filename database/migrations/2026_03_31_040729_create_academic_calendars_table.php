<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->string('event_name', 191);
            $table->enum('event_type', [
                'hari_efektif',
                'libur_nasional',
                'libur_semester',
                'libur_ponpes',
                'ujian_harian',
                'pts',          // Penilaian Tengah Semester
                'pas',          // Penilaian Akhir Semester
                'ujian_sekolah',
                'kegiatan_ponpes',
                'rapat',
                'lainnya',
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('is_all_schools')->default(0)
                  ->comment('1 = berlaku untuk semua sekolah dalam satu ponpes');
            $table->string('color', 20)->nullable()
                  ->comment('Kode warna hex untuk tampilan kalender, misal: #FF5733');
            $table->text('description')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
 
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
 
            $table->index(['school_id', 'academic_year_id']);
            $table->index(['school_id', 'start_date', 'end_date']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
    }
};
