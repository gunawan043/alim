<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_visitor_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id')->index();
            $table->string('visitor_name', 191);
            $table->string('id_number', 50)->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->text('reason');
            $table->boolean('is_active')->default(true)->index();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index(['dormitory_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_visitor_restrictions');
    }
};
