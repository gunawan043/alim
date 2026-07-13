<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_policy_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('policy_assignment_type', 32)->default('dormitory')
                ->comment('dormitory | semester_program | student_category');
            $table->uuid('target_id')->nullable()->comment('FK to dormitory, program, or category table.');
            $table->uuid('boarding_policy_id');
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();
            $table->unsignedInteger('priority')->default(0)->comment('Higher priority overrides lower.');
            $table->timestamps();

            $table->unique(['target_id', 'policy_assignment_type'], 'target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_policy_assignments');
    }
};
