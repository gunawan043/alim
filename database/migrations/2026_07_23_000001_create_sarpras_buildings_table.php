<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sarpras_buildings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->enum('gender', ['putra', 'putri', 'campur'])->default('campur');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
            $table->index('school_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarpras_buildings');
    }
};
