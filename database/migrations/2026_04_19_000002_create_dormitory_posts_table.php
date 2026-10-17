<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->string('title');
            $table->longText('content');
            $table->enum('category', ['pengumuman', 'undangan', 'laporan', 'darurat'])->default('pengumuman');
            $table->enum('visibility', ['wali', 'pengurus', 'umum'])->default('wali');
            $table->boolean('needs_response')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('attachment_path')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dormitory_id')->references('id')->on('dormitories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('dormitory_id');
            $table->index('category');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_posts');
    }
};
