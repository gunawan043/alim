<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id');
            $table->string('code', 20)->unique();
            $table->string('name', 191);
            $table->enum('gender', ['putra', 'putri', 'campuran']);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->integer('capacity')->default(0);
            $table->integer('total_rooms')->default(0);
            $table->integer('total_wings')->default(0);
            $table->uuid('head_id')->nullable()->comment('Kepala asrama');
            $table->tinyInteger('is_active')->default(1);
            $table->string('logo_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('head_id')->references('id')->on('users')->nullOnDelete();
 
            $table->index('school_id');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitories');
    }
};

