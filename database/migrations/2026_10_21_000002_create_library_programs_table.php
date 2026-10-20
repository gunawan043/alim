<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('library_programs')) {
            Schema::create('library_programs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->nullable()->constrained('library_units')->nullOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('type', 30);
                $table->string('title');
                $table->string('category')->nullable();
                $table->string('status', 30)->default('draft');
                $table->date('event_date')->nullable();
                $table->unsignedInteger('quantity')->nullable();
                $table->decimal('amount', 14, 2)->nullable();
                $table->text('description')->nullable();
                $table->json('data')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['library_unit_id', 'type', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('library_programs');
    }
};
