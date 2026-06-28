<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('name', 191);
            $table->string('color', 20)->nullable();
            $table->tinyInteger('is_default')->default(0)
                  ->comment('1 = daftar default, tidak bisa dihapus');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
 
            $table->foreign('user_id')
                  ->references('id')->on('users')->cascadeOnDelete();
 
            $table->index(['user_id', 'sort_order']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('todo_lists');
    }
};
