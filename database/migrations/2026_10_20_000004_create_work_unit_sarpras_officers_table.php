<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_unit_sarpras_officers')) {
            return;
        }

        Schema::create('work_unit_sarpras_officers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('user_id');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->uuid('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['work_unit_id', 'user_id'], 'unit_sarpras_officer_unique');
            $table->index(['work_unit_id', 'is_active', 'is_primary'], 'unit_sarpras_officer_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_unit_sarpras_officers');
    }
};
