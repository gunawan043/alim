<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uks_status_histories')) {
            Schema::create('uks_status_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->timestamp('changed_at')->index();
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index(['patient_id', 'changed_at'], 'idx_status_history_patient_time');
            });
        }

        // Add patient_id foreign key only if uks_patients exists
        if (Schema::hasTable('uks_patients') && Schema::hasColumn('uks_status_histories', 'patient_id')) {
            $fkExists = collect(Schema::getForeignKeys('uks_status_histories'))
                ->contains(fn ($fk) => in_array('patient_id', $fk['columns'] ?? [], true));

            if (! $fkExists) {
                Schema::table('uks_status_histories', function (Blueprint $table) {
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
        Schema::dropIfExists('uks_status_histories');
    }
};
