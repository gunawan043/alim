<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gtk_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtk_profile_id')->constrained()->cascadeOnDelete();

            $table->enum('relationship', [
                'suami',
                'istri',
                'anak',
                'ayah',
                'ibu',
            ]);

            $table->text('nama');
            $table->string('pekerjaan')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('pendidikan_terakhir')->nullable(); // untuk anak
            $table->string('alamat')->nullable();

            $table->date('tanggal_lahir')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_family_members');
    }
};
