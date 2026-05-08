<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_permits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('dormitory_id');
            $table->uuid('room_id');
            $table->uuid('academic_year_id');
            $table->enum('permit_type', [
                'pulang',
                'keluar_kota',
                'berobat',
                'keperluan_keluarga',
                'lainnya',
            ]);
            $table->string('destination', 191)->nullable()->comment('Tujuan kepergian');
            $table->text('purpose')->nullable()->comment('Keperluan / alasan');
            $table->dateTime('departure_datetime');
            $table->dateTime('expected_return_datetime');
            $table->dateTime('actual_return_datetime')->nullable();
            $table->string('companion_name', 191)->nullable()->comment('Nama penjemput / wali');
            $table->string('companion_relation', 100)->nullable();
            $table->string('companion_phone', 20)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned', 'overdue'])
                  ->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->string('document_path', 255)->nullable();
            $table->uuid('created_by');
            $table->timestamps();
 
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
 
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['dormitory_id', 'status']);
            $table->index('departure_datetime');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_permits');
    }
};
