<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uks_medication_logs')) {
            Schema::create('uks_medication_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->foreignUuid('administered_by')->nullable()->constrained('users');

                $table->string('medicine_name');
                $table->string('dosage')->nullable()->comment('Dosis, mis. "1 tablet", "5 ml"');
                $table->string('route')->default('oral')->comment('Rute pemberian: oral, topikal, injeksi, dll');
                $table->timestamp('given_at');
                $table->text('notes')->nullable();

                $table->boolean('is_scheduled')->default(false);
                $table->json('schedule')->nullable()->comment('Jadwal pemberian obat, mis. [{"frequency":"3x1","duration":3}]');

                $table->timestamps();

                $table->index(['patient_id', 'given_at']);
            });
        }

        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_medication_logs', 'patient_id')) {
            $fkExists = collect(
                \DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'uks_medication_logs'
                     AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [config('database.connections.mysql.database')]
                )
            )->isNotEmpty();

            if (! $fkExists) {
                Schema::table('uks_medication_logs', function (Blueprint $table) {
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
        Schema::dropIfExists('uks_medication_logs');
    }
};
