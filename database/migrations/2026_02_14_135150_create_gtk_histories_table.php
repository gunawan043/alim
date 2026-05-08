<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('academic_year_id')
                  ->constrained('academic_years')
                  ->cascadeOnDelete();
            
            // Bisa merujuk ke berbagai jenis penempatan
            $table->uuidMorphs('assignable'); // This already creates the index automatically!
            
            $table->string('assignment_type'); // structural, teaching, homeroom, coordinator
            $table->json('assignment_data')->nullable(); // Snapshot data penempatan
            
            $table->timestamps();
            
            // Keep this index as it's different
            $table->index(['user_id', 'academic_year_id'], 'gtk_histories_user_academic_idx');
            
            // REMOVE THIS LINE - it's already created by uuidMorphs()
            // $table->index(['assignable_type', 'assignable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_histories');
    }
};