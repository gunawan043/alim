<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_mahroms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->string('name');
            $table->string('id_number', 30)->nullable();
            $table->enum('relationship', [
                'ayah', 'ibu', 'kakak', 'adik',
                'paman', 'bibi', 'kakek', 'nenek',
                'suami', 'istri', 'sepupu', 'wali',
                'anak', 'lainnya',
            ]);
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unique(['student_id', 'id_number']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_mahroms');
    }
};
