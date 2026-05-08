<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->string('name', 191);
            $table->string('code', 20)->nullable();
            $table->enum('category', [
                'olahraga',
                'seni',
                'akademik',
                'keagamaan',
                'organisasi',
                'keterampilan',
                'lainnya',
            ]);
            $table->text('description')->nullable();
            $table->uuid('supervisor_id')->nullable()->comment('Guru pembina ekskul');
            $table->uuid('instructor_id')->nullable()->comment('Pelatih / instruktur dari luar');
            $table->string('schedule_day', 100)->nullable()->comment('Hari latihan, misal: Senin, Rabu');
            $table->time('schedule_time_start')->nullable();
            $table->time('schedule_time_end')->nullable();
            $table->string('room', 100)->nullable();
            $table->integer('max_members')->nullable();
            $table->tinyInteger('is_mandatory')->default(0)
                  ->comment('1 = wajib diikuti semua santri');
            $table->tinyInteger('is_active')->default(1);
            $table->string('logo_path', 255)->nullable();
            $table->timestamps();
 
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('instructor_id')->references('id')->on('users')->nullOnDelete();
 
            $table->unique(['school_id', 'code'], 'unique_extracurricular_code_per_school');
            $table->index(['school_id', 'is_active']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('extracurriculars');
    }
};
