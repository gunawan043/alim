<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_counseling_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->uuid('counselor_id');
            $table->date('session_date');
            $table->integer('session_number')->default(1)
                ->comment('Pertemuan ke-N untuk kasus yang sama');
            $table->enum('session_type', [
                'individu',
                'kelompok',
                'krisis',
            ]);
            $table->string('topic', 191)->nullable();
            $table->text('description')->nullable()
                ->comment('Catatan sesi konseling');
            $table->text('analysis')->nullable()
                ->comment('Analisis / asesmen konselor');
            $table->text('follow_up_plan')->nullable()
                ->comment('Rencana tindak lanjut');
            $table->tinyInteger('referral_needed')->default(0)
                ->comment('1 = perlu dirujuk ke profesional');
            $table->string('referred_to', 191)->nullable()
                ->comment('Tujuan rujukan');
            $table->date('next_session_date')->nullable();
            $table->tinyInteger('parent_informed')->default(0);
            $table->timestamp('parent_informed_at')->nullable();
            $table->uuid('parent_informed_by')->nullable();
            $table->tinyInteger('is_confidential')->default(1)
                ->comment('1 = rahasia, hanya konselor & kepala sekolah');
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('counselor_id')->references('id')->on('users');
            $table->foreign('parent_informed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['student_id', 'academic_year_id']);
            $table->index(['counselor_id', 'session_date']);
            $table->index(['school_id', 'session_date']);
            $table->index(['referral_needed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_counseling_records');
    }
};
