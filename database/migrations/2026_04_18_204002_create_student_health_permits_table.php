<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_health_permits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->uuid('dormitory_id')->nullable()
                  ->comment('Diisi jika santri saat itu menetap di asrama');
            $table->enum('permit_type', [
                'sakit_ringan',
                'sakit_sedang',
                'sakit_berat',
                'kontrol_dokter',
                'isolasi',
            ]);
            $table->text('description')->nullable()
                  ->comment('Deskripsi keluhan / diagnosis awal');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('rest_days')->default(0)
                  ->comment('Jumlah hari istirahat yang direkomendasikan');
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'extended',
                'cancelled',
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->tinyInteger('parent_notified')->default(0)
                  ->comment('1 = orang tua sudah diinformasikan');
            $table->timestamp('parent_notified_at')->nullable();
            $table->uuid('parent_notified_by')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('parent_notified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['student_id', 'academic_year_id']);
            $table->index(['student_id', 'status']);
            $table->index(['school_id', 'status']);
            $table->index(['academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_permits');
    }
};