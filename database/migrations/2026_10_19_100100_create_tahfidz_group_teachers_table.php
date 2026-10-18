<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_group_teachers')) {
            Schema::create('tahfidz_group_teachers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tahfidz_group_id')->index();
                $table->uuid('user_id')->index();
                $table->string('role', 30)->default('guru_utama');
                $table->boolean('can_input_setoran')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tahfidz_group_id', 'user_id', 'role'], 'tahfidz_group_teacher_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_group_teachers');
    }
};
