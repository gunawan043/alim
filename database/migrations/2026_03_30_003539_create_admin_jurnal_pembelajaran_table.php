<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_jurnal_pembelajaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->integer('meeting_number')->comment('Pertemuan ke-');
            $table->date('meeting_date')->comment('Hari/Tanggal');
            $table->time('time_in')->nullable()->comment('Jam Masuk');
            $table->time('time_out')->nullable()->comment('Jam Keluar');
            $table->text('material')->nullable()->comment('Materi / Kegiatan');
            $table->string('teacher_signature', 255)->nullable()->comment('Paraf Guru');
            $table->string('class_leader_signature', 255)->nullable()->comment('Paraf Ketua Kelas');
            $table->timestamps();
 
            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
 
            $table->unique(['admin_book_id', 'meeting_number'], 'unique_meeting_per_book');
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_presensi_guru'
            );
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('admin_jurnal_pembelajaran');
    }
};
