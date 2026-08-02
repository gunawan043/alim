<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_clinic_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('dormitory_id')->nullable()
                ->comment('Diisi jika kunjungan berasal dari laporan asrama');
            $table->uuid('academic_year_id');
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->text('complaint')->nullable()->comment('Keluhan yang disampaikan santri');
            $table->text('diagnosis')->nullable()->comment('Diagnosa petugas klinik / UKS');
            $table->text('treatment')->nullable()->comment('Tindakan penanganan');
            $table->text('medicine_given')->nullable()->comment('Obat yang diberikan beserta dosis');
            $table->integer('rest_days')->default(0)
                ->comment('Jumlah hari istirahat yang direkomendasikan');
            $table->tinyInteger('is_referred')->default(0)
                ->comment('1 = dirujuk ke fasilitas kesehatan luar');
            $table->string('referral_hospital', 191)->nullable();
            $table->text('referral_reason')->nullable();
            $table->uuid('handled_by')->comment('Petugas klinik / UKS yang menangani');
            $table->tinyInteger('is_serious')->default(0)
                ->comment('1 = kondisi serius / darurat, trigger notifikasi ke wali');
            $table->timestamp('parent_notified_at')->nullable();
            $table->uuid('parent_notified_by')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('handled_by')->references('id')->on('users');
            $table->foreign('parent_notified_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['student_id', 'visit_date']);
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'visit_date']);
            $table->index(['is_serious', 'parent_notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_clinic_visits');
    }
};
