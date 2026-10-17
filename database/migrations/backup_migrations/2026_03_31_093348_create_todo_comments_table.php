<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id');
            $table->uuid('user_id');
            $table->text('comment');
            $table->uuid('parent_comment_id')->nullable()
                ->comment('Jika ini adalah balasan dari komentar lain');
            $table->tinyInteger('is_edited')->default(0);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->foreign('todo_id')
                ->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_comment_id')
                ->references('id')->on('todo_comments')->nullOnDelete();

            $table->index(['todo_id', 'created_at']);
            $table->index('parent_comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_comments');
    }
};
