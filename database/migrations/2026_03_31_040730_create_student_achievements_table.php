<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('achievement_type', [
                'akademik',
                'non_akademik',
                'hafalan',
                'olahraga',
                'seni',
                'sains',
                'lainnya',
            ]);
            $table->string('event_name', 191)->comment('Nama lomba / kompetisi / kejuaraan');
            $table->string('organizer', 191)->nullable()->comment('Penyelenggara acara');
            $table->enum('level', [
                'internal',
                'kecamatan',
                'kabupaten_kota',
                'provinsi',
                'nasional',
                'internasional',
            ]);
            $table->enum('position', [
                'juara_1', 'juara_2', 'juara_3',
                'harapan_1', 'harapan_2', 'harapan_3',
                'peserta', 'lainnya',
            ]);
            $table->string('position_detail', 100)->nullable()
                  ->comment('Detail posisi jika lainnya, misal: Medali Emas, Best Paper');
            $table->date('event_date');
            $table->string('event_location', 191)->nullable();
            $table->uuid('coach_id')->nullable()->comment('Guru pembimbing / pelatih');
            $table->string('certificate_path', 255)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
 
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('coach_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
 
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'academic_year_id', 'level']);
            $table->index(['school_id', 'achievement_type']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
 