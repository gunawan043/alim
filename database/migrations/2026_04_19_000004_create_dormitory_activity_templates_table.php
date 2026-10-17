<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_activity_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->string('session'); // subuh, siang, malam
            $table->json('activity_items'); // [{"key":"mengaji","label":"Mengaji","type":"text"},...]
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('dormitory_id')->references('id')->on('dormitories')->onDelete('cascade');
            $table->unique(['dormitory_id', 'session']);
            $table->index('dormitory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_activity_templates');
    }
};
