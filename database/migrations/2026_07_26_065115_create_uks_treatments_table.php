<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create table only if it doesn't exist (in case of partial run)
        if (! Schema::hasTable('uks_treatments')) {
            Schema::create('uks_treatments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->foreignUuid('performed_by')->nullable()->constrained('users');

                // Symptom / vitals snapshot
                $table->text('chief_complaint')->nullable();
                $table->json('symptoms')->nullable()->comment('Gejala tambahan saat pemeriksaan');
                $table->json('vitals')->nullable()->comment('BP, suhu, nadi, TB, BB, saturasi pada saat pemeriksaan');

                // Diagnosis & treatment
                $table->string('diagnosis')->nullable();
                $table->text('treatment')->nullable()->comment('Tindakan medis yang diberikan');
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['patient_id', 'created_at']);
            });
        }

        // Add patient_id foreign key only if uks_patients table exists
        // (otherwise it will be added by a later migration once patients table is created)
        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_treatments', 'patient_id')) {
            $fkExists = collect(
                \DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'uks_treatments'
                     AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [config('database.connections.mysql.database')]
                )
            )->isNotEmpty();

            if (! $fkExists) {
                Schema::table('uks_treatments', function (Blueprint $table) {
                    $table->foreign('patient_id')
                        ->references('id')
                        ->on('uks_patients')
                        ->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_treatments');
    }
};
