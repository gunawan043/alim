<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id');
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->unsignedBigInteger('file_size')->nullable()
                ->comment('Ukuran file dalam bytes');
            $table->string('file_type', 50)->nullable()
                ->comment('MIME type, misal: application/pdf, image/jpeg');
            $table->uuid('uploaded_by');
            $table->timestamps();

            $table->foreign('todo_id')
                ->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('uploaded_by')
                ->references('id')->on('users');

            $table->index('todo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_attachments');
    }
};
