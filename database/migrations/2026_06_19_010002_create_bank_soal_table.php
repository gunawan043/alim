<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_soal', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('subject_id');
            $table->string('fase', 5)->nullable();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->enum('jenis_soal', [
                'pilihan_ganda',
                'multiple_choice_complex',
                'benar_salah',
                'menjodohkan',
                'isian_singkat',
                'uraian',
                'campuran',
            ])->default('campuran');
            $table->enum('tingkat_kesulitan_target', ['mudah', 'sedang', 'sulit', 'campuran'])->default('campuran');
            $table->boolean('is_public')->default(false);
            $table->enum('shared_scope', ['private', 'internal_school', 'public_pool'])->default('private');
            $table->uuid('owner_user_id')->nullable();
            $table->boolean('allow_cross_teacher_clone')->default(false);
            $table->unsignedInteger('total_soal')->default(0);
            $table->json('distribusi_kesulitan_aktual')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['school_id', 'subject_id', 'fase', 'owner_user_id'], 'bank_soal_school_subject_fase_owner_idx');
            $table->index('shared_scope', 'bank_soal_shared_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soal');
    }
};
