<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('additional_task_types')) {
            return;
        }

        Schema::create('additional_task_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('jenis_gtk_id')->constrained('jenis_gtk')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->string('kode', 50)->unique();
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_task_types');
    }
};
