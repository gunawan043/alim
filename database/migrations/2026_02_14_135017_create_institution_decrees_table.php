<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_decrees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Informasi SK
            $table->string('decree_number', 100)->unique(); // Nomor SK
            $table->string('decree_type', 50); // SK Kepsek, SK Pembagian Tugas, dll
            $table->string('title', 255); // Judul SK
            $table->text('description')->nullable();
            
            // Masa berlaku
            $table->foreignUuid('academic_year_id')
                  ->constrained('academic_years')
                  ->cascadeOnDelete();
            
            $table->date('issued_date'); // Tanggal ditetapkan
            $table->date('effective_date'); // Tanggal mulai berlaku
            $table->date('end_date')->nullable(); // Tanggal berakhir
            
            // Penandatangan
            $table->foreignUuid('signed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->string('signed_position', 100)->nullable(); // Jabatan penandatangan
            
            // File SK
            $table->string('document_path', 255)->nullable();
            $table->string('document_filename', 255)->nullable();
            
            // Status
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('decree_number');
            $table->index('decree_type');
            $table->index('academic_year_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_decrees');
    }
};