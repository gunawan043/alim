<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_room_moves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('from_room_id');
            $table->uuid('to_room_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id');
            $table->date('move_date');
            $table->text('reason')->nullable();
            $table->enum('move_type', [
                'rotasi',
                'permintaan',
                'sanksi',
                'kondisi_kesehatan',
                'lainnya',
            ]);
            $table->uuid('approved_by')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
 
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('from_room_id')->references('id')->on('dormitory_rooms');
            $table->foreign('to_room_id')->references('id')->on('dormitory_rooms');
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
 
            $table->index(['student_id', 'academic_year_id']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_room_moves');
    }
};
