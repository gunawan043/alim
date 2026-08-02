<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing patient_id foreign key constraints to UKS tables.
     * This runs AFTER create_uks_patients_table so the parent table exists.
     */
    public function up(): void
    {
        $tables = [
            'uks_treatments',
            'uks_medication_logs',
            'uks_care_events',
            'uks_treatment_notes',
            'uks_medication_administrations',
            'uks_status_histories',
        ];

        foreach ($tables as $table) {
            // Only add FK if column exists and no existing FK to uks_patients
            if (Schema::hasColumn($table, 'patient_id')) {
                $fkExists = collect(
                    DB::select(
                        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                         AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME = 'uks_patients'",
                        [config('database.connections.mysql.database'), $table]
                    )
                )->isNotEmpty();

                if (! $fkExists) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->foreign('patient_id')
                            ->references('id')
                            ->on('uks_patients')
                            ->onDelete('cascade');
                    });
                    echo "Added FK: {$table}.patient_id -> uks_patients.id\n";
                } else {
                    echo "FK already exists on: {$table}\n";
                }
            } else {
                echo "Warning: {$table} does not have patient_id column\n";
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'uks_treatments',
            'uks_medication_logs',
            'uks_care_events',
            'uks_treatment_notes',
            'uks_medication_administrations',
            'uks_status_histories',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['patient_id']);
            });
        }
    }
};
