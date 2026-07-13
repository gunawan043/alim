<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('todo_subtasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id');
            $table->string('title', 255);
            $table->tinyInteger('is_completed')->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
 
            $table->foreign('todo_id')
                  ->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('completed_by')
                  ->references('id')->on('users')->nullOnDelete();
 
            $table->index(['todo_id', 'is_completed', 'sort_order']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('todo_subtasks');
    }
};
