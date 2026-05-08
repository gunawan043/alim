<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_immunizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->enum('immunization_type', [
                'BCG',
                'Polio_1', 'Polio_2', 'Polio_3', 'Polio_4',
                'DPT_HB_Hib_1', 'DPT_HB_Hib_2', 'DPT_HB_Hib_3',
                'Campak_MR', 'MR_2',
                'Hepatitis_B',
                'TT_1', 'TT_2', 'TT_3', 'TT_4', 'TT_5',
                'Covid19',
                'Influenza',
                'Japanese_Encephalitis',
                'lainnya',
            ]);
            $table->string('vaccine_name')->nullable();
            $table->date('date_given');
            $table->integer('age_at_vaccination_days')->nullable()
                  ->comment('Umur saat vaksin dalam hari');
            $table->string('place', 191)->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->text('side_effects')->nullable()
                  ->comment('Efek samping yang muncul');
            $table->string('medical_staff', 191)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->index(['student_id', 'immunization_type']);
            $table->index(['student_id', 'date_given']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_immunizations');
    }
};