<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dormitory_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('dormitory_id')->nullable()->constrained('dormitories')->nullOnDelete();
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('title');
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('level')->default('biasa');
            $table->string('proof_path')->nullable();
            $table->foreignUuid('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('awarded_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_rewards');
    }
};
