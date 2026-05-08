<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_catatan_guru', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_book_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->date('note_date')->comment('Hari/Tanggal catatan');
            $table->text('student_note')->nullable()->comment('Catatan tentang Peserta Didik');
            $table->text('learning_note')->nullable()->comment('Catatan tentang Proses Pembelajaran');
            $table->timestamps();
 
            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
 
            $table->index(
                ['admin_book_id', 'academic_year_id', 'semester'],
                'idx_presensi_guru'
            );
            $table->index(['admin_book_id', 'note_date']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('admin_catatan_guru');
    }
};