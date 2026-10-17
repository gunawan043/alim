<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_watchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id');
            $table->uuid('user_id');
            $table->uuid('added_by');
            $table->timestamps();

            $table->foreign('todo_id')
                ->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('added_by')
                ->references('id')->on('users');

            // Satu user hanya bisa jadi watcher sekali per todo
            $table->unique(['todo_id', 'user_id'], 'unique_todo_watcher');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_watchers');
    }
};
