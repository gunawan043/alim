<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_staff_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('dormitory_id')
                ->constrained('dormitories')
                ->cascadeOnDelete();

            $table->foreignUuid('assigned_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'ended'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'dormitory_id'], 'dsa_user_dormitory_unique');
            $table->index('user_id');
            $table->index('dormitory_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_staff_assignments');
    }
};
