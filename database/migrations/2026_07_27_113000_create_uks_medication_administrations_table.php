<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uks_medication_administrations')) {
            Schema::create('uks_medication_administrations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->foreignUuid('administered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('medicine_name');
                $table->string('dosage')->nullable();
                $table->unsignedInteger('quantity')->default(1)->comment('Jumlah obat yang diberikan');
                $table->timestamp('given_at')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['patient_id', 'given_at'], 'idx_med_admin_patient_time');
            });
        }

        // Add patient_id foreign key only if uks_patients exists
        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_medication_administrations', 'patient_id')) {
            $fkExists = collect(
                \DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'uks_medication_administrations'
                     AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [config('database.connections.mysql.database')]
                )
            )->isNotEmpty();

            if (! $fkExists) {
                Schema::table('uks_medication_administrations', function (Blueprint $table) {
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
        Schema::dropIfExists('uks_medication_administrations');
    }
};
