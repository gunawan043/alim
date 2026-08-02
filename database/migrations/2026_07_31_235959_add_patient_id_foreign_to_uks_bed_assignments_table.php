<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add patient_id foreign key to uks_bed_assignments table.
     *
     * This runs AFTER create_uks_patients_table to ensure the parent table exists.
     * Note: The patient_id column already exists from the original create migration,
     * so we only add the foreign key constraint.
     */
    public function up(): void
    {
        // Check if the column and reference table exist before adding FK
        if (Schema::hasColumn('uks_bed_assignments', 'patient_id') && Schema::hasTable('uks_patients')) {
            // Check if foreign key already exists
            $fkExists = collect(
                DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'uks_bed_assignments'
                     AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [config('database.connections.mysql.database')]
                )
            )->isNotEmpty();

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

    /**
     * Reverse the migration - drop the foreign key constraint.
     */
    public function down(): void
    {
        Schema::table('uks_bed_assignments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });
    }
};
