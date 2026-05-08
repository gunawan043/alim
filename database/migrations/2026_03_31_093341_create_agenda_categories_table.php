<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agenda_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id')->nullable()
                  ->comment('NULL = kategori global untuk seluruh ponpes');
            $table->string('name', 100);
            $table->string('color', 20)->nullable()
                  ->comment('Kode warna hex, misal: #3B82F6');
            $table->string('icon', 50)->nullable()
                  ->comment('Nama icon, misal: calendar, flag, users, book');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
 
            $table->foreign('work_unit_id')
                  ->references('id')->on('work_units')->nullOnDelete();
 
            $table->index(['work_unit_id', 'is_active']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('agenda_categories');
    }
};
