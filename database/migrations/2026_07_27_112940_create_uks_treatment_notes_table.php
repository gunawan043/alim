<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uks_treatment_notes')) {
            Schema::create('uks_treatment_notes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('recorded_at')->index();
                $table->text('note');
                $table->timestamps();

                $table->index(['patient_id', 'recorded_at'], 'idx_treatment_notes_patient_time');
            });
        }

        // Add patient_id foreign key only if uks_patients exists
        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_treatment_notes', 'patient_id')) {
            $fkExists = collect(
                \DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'uks_treatment_notes'
                     AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [config('database.connections.mysql.database')]
                )
            )->isNotEmpty();

            if (! $fkExists) {
                Schema::table('uks_treatment_notes', function (Blueprint $table) {
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
        Schema::dropIfExists('uks_treatment_notes');
    }
};
