<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prescription_id')->constrained('uks_prescriptions')->cascadeOnDelete();
            $table->foreignUuid('inventory_id')->nullable()->constrained('student_medicine_inventory')->nullOnDelete();

            $table->string('medicine_name', 191);
            $table->string('dosage', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('route', 30)->default('oral');
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('dispensed_quantity', 10, 2)->default(0);
            $table->text('instructions')->nullable();

            $table->timestamps();

            $table->index('prescription_id', 'uks_prescription_items_prescription_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_prescription_items');
    }
};
