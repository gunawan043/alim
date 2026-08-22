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
        // SQLite does not support information_schema queries
        $tables = [
            'uks_treatments',
            'uks_medication_logs',
            'uks_care_events',
            'uks_treatment_notes',
            'uks_medication_administrations',
            'uks_status_histories',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'patient_id') && Schema::hasTable('uks_patients')) {
                Schema::table($table, function (Blueprint $table) {
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
