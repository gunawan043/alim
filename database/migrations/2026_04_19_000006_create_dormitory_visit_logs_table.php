<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_visit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->uuid('room_id')->nullable();
            $table->uuid('student_id');
            $table->string('visitor_name', 191);
            $table->string('visitor_id_number', 30)->nullable()->comment('NIK / KTP visitor');
            $table->string('visitor_phone', 20)->nullable();
            $table->enum('visitor_relationship', [
                'mahrom', 'wali', 'keluarga', 'Pihak pondok', ' Lainnya',
            ])->default(' Lainnya');
            $table->enum('purpose', [
                'menjenguk', 'bawa_bantuan', 'pertemuan_wali',
                'antar_jemput', 'lainnya',
            ])->default('menjenguk');
            $table->dateTime('expected_arrival_datetime');
            $table->dateTime('actual_arrival_datetime')->nullable();
            $table->dateTime('departure_datetime')->nullable();
            $table->integer('expected_meet_duration_minutes')->nullable()->default(60);
            $table->text('notes')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->enum('status', [
                'pending', 'approved', 'rejected',
                'arrived', 'checked_out', 'cancelled', 'no_show',
            ])->default('pending');
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('dormitory_id')->references('id')->on('dormitories')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->nullOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['dormitory_id', 'status']);
            $table->index(['student_id', 'created_at']);
            $table->index('expected_arrival_datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_visit_logs');
    }
};
