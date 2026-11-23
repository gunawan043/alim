<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_card_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('front_background_path')->nullable();
            $table->string('back_background_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signature_name')->nullable();
            $table->string('signature_title')->nullable();
            $table->string('validity_text')->nullable();
            $table->decimal('card_width_mm', 6, 2)->default(85.60);
            $table->decimal('card_height_mm', 6, 2)->default(53.98);
            $table->json('layout')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_card_settings');
    }
};
