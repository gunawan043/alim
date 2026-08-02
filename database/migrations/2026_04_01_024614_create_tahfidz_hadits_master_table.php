<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 27. create_tahfidz_hadits_master_table.php
// Master 42 Hadits Arbain An-Nawawi. Di-seed sekali.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_hadits_master', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->tinyInteger('hadits_number')->unsigned()->unique();
            $table->string('topic', 191)->comment('Tema/topik hadits: Niat, Islam, dll');
            $table->text('arabic_text')->nullable();
            $table->string('narrator', 100)->nullable()->comment('Bukhari, Muslim, Abu Dawud, dll');
            $table->enum('difficulty_level', ['mudah', 'sedang', 'sulit'])->default('sedang');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_hadits_master');
    }
};
