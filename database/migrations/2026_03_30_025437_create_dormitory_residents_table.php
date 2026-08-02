<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_residents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('room_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id');
            $table->tinyInteger('bed_number')->nullable()->comment('Nomor tempat tidur di kamar');
            $table->tinyInteger('is_active')->default(1);
            $table->date('check_in_date');
            $table->date('check_out_date')->nullable();
            $table->enum('check_out_reason', [
                'lulus', 'pindah_kamar', 'keluar', 'sakit', 'lainnya',
            ])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            // Satu santri hanya boleh tinggal di satu kamar aktif dalam satu tahun ajaran
            $table->unique(
                ['student_id', 'academic_year_id', 'is_active'],
                'unique_active_resident_per_year'
            );
            $table->index(['room_id', 'academic_year_id']);
            $table->index(['dormitory_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_residents');
    }
};
