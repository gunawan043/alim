<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_post_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->uuid('student_id');
            $table->string('parent_name')->nullable();
            $table->enum('response_type', ['ack', 'question', 'complaint'])->default('ack');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('dormitory_posts')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unique(['post_id', 'student_id']);
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_post_responses');
    }
};
