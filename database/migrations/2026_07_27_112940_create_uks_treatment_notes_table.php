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
            $fkExists = collect(Schema::getForeignKeys('uks_treatment_notes'))
                ->contains(fn ($fk) => in_array('patient_id', $fk['columns'] ?? [], true));

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
