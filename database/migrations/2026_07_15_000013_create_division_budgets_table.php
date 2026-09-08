<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division_budgets', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignUuid('division_id')->constrained('divisis')->cascadeOnDelete();
            $table->unsignedInteger('fiscal_year');
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->decimal('used_amount', 14, 2)->default(0);
            $table->decimal('reserved_amount', 14, 2)->default(0);
            $table->string('last_purpose')->nullable();
            $table->timestamps();

            $table->unique(['division_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_budgets');
    }
};
