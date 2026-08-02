<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uks_care_events')) {
            Schema::create('uks_care_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->foreignUuid('performed_by')->nullable()->constrained('users');

                // Event metadata for timeline rendering
                $table->timestamp('happened_at');
                $table->string('event_type')->comment('masuk, pemeriksaan, pemberian_obat, istirahat, pemeriksaan_ulang, pulang, dirujuk, dll');
                $table->string('event_title');
                $table->text('description')->nullable();

                $table->timestamps();

                $table->index(['patient_id', 'happened_at']);
            });
        }

        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_care_events', 'patient_id')) {
            $fkExists = collect(
                \DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'uks_care_events'
                     AND COLUMN_NAME = 'patient_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [config('database.connections.mysql.database')]
                )
            )->isNotEmpty();

            if (! $fkExists) {
                Schema::table('uks_care_events', function (Blueprint $table) {
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
        Schema::dropIfExists('uks_care_events');
    }
};
