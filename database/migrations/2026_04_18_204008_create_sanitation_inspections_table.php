<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanitation_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->date('inspection_date');
            $table->uuid('inspected_by');
            $table->enum('location_type', [
                'asrama',
                'kantin',
                'toilet',
                'tempat_sampah',
                'sumber_air',
                'ruang_kelas',
                'halaman',
                'dapur',
            ]);
            $table->uuid('location_id')->nullable()
                ->comment('FK ke dormitory_rooms / dormitories sesuai location_type');
            $table->integer('score')
                ->comment('Skor 0-100');
            $table->text('findings')->nullable()
                ->comment('Temuan hasil inspeksi');
            $table->string('photo_path', 255)->nullable();
            $table->text('recommendations')->nullable();
            $table->date('follow_up_deadline')->nullable();
            $table->timestamp('follow_up_completed_at')->nullable();
            $table->tinyInteger('is_passed')->nullable()
                ->comment('1 = lulus standar, 0 = perlu perbaikan');
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('inspected_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['school_id', 'academic_year_id']);
            $table->index(['location_type', 'inspection_date']);
            $table->index(['follow_up_deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanitation_inspections');
    }
};
