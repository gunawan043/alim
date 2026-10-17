<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_work_unit', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('work_unit_id')->constrained()->cascadeOnDelete();

            $table->string('jabatan')->nullable();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'work_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_work_unit');
    }
};
