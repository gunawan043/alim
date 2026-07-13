<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division_inventories', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('division_id', 36);
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignUuid('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('custody_since')->nullable();
            $table->timestamps();
            $table->unique(['division_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_inventories');
    }
};