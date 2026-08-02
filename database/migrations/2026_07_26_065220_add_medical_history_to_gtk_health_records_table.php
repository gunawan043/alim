<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create gtk_medical_histories as a separate table for full history tracking
        if (! Schema::hasTable('gtk_medical_histories')) {
            Schema::create('gtk_medical_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained()->onDelete('cascade');

                // Medical history checkboxes (stored as JSON for flexible additions)
                $table->json('history_conditions')->nullable()
                    ->comment('[{"name":"Hipertensi","diagnosed_at":"2020-01-01","status":"controlled"}]');

                // Chronic diseases
                $table->string('hypertension')->nullable()->comment('Ya/Tidak');
                $table->string('diabetes')->nullable()->comment('Ya/Tidak');
                $table->string('asthma')->nullable()->comment('Ya/Tidak');
                $table->string('heart_disease')->nullable()->comment('Ya/Tidak');
                $table->string('kidney_disease')->nullable()->comment('Ya/Tidak');
                $table->string('hepatitis')->nullable()->comment('Ya/Tidak');
                $table->string('tb')->nullable()->comment('Ya/Tidak');
                $table->string('allergies')->nullable()->comment('Ya/Tidak — jelaskan di allergy_details');
                $table->text('allergy_details')->nullable();
                $table->text('other_conditions')->nullable();

                // Medication history
                $table->json('regular_medications')->nullable()
                    ->comment('[{"name":"Amlodipine 5mg","frequency":"1x1","duration":"ongoing"}]');

                // Surgery & hospitalization
                $table->json('surgery_history')->nullable()
                    ->comment('[{"name":"Operasi usus","date":"2018-05-10","hospital":"RS Umum X"}]');
                $table->json('hospitalization_history')->nullable()
                    ->comment('[{"reason":"Demam berdarah","date":"2019-03-15","duration_days":5}]');
                $table->json('accident_history')->nullable()
                    ->comment('[{"description":"Kecelakaan lalu lintas","date":"2021-11-20","injury_type":"luka sayat"}]');

                $table->timestamps();
                $table->index(['user_id']);
            });
        }

        // Add health_history reference to gtk_health_records (use 'source' since 'examined_by' doesn't exist in old schema)
        Schema::table('gtk_health_records', function (Blueprint $table) {
            $table->foreignUuid('medical_history_id')->nullable()
                ->after('source')
                ->constrained('gtk_medical_histories')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('gtk_health_records', function (Blueprint $table) {
            $table->dropForeign(['medical_history_id']);
            $table->dropColumn('medical_history_id');
        });
        Schema::dropIfExists('gtk_medical_histories');
    }
};
