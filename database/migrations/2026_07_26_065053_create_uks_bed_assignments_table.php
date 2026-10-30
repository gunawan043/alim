<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create table only if it doesn't exist (table may already exist from a previous partial run)
        if (! Schema::hasTable('uks_bed_assignments')) {
            Schema::create('uks_bed_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('bed_id')->constrained('uks_beds')->onDelete('cascade');
                $table->uuid('patient_id');
                $table->timestamp('assigned_at');
                $table->timestamp('released_at')->nullable();
                $table->enum('status', ['assigned', 'released', 'extended'])->default('assigned');
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index(['bed_id', 'status']);
                $table->index(['patient_id', 'status']);
            });
        }

        // Add patient_id foreign key only if the parent table exists
        // (it will be added later by 2026_07_31_235959_add_patient_id_foreign_to_uks_bed_assignments_table)
        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_bed_assignments', 'patient_id')) {
            $fkExists = collect(Schema::getForeignKeys('uks_bed_assignments'))
                ->contains(fn ($fk) => in_array('patient_id', $fk['columns'] ?? [], true));

            if (! $fkExists) {
                Schema::table('uks_bed_assignments', function (Blueprint $table) {
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
        Schema::dropIfExists('uks_bed_assignments');
    }
};
