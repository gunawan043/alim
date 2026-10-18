<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banyak alur Sarpras memakai status lifecycle (active/lost/damaged/under_repair),
        // sedangkan enum lama hanya mengizinkan empat nilai. Ubah ke string agar konsisten.
        Schema::table('assets', function (Blueprint $table) {
            $table->string('status', 30)->default('tersedia')->change();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->enum('status', ['tersedia', 'dipinjam', 'dalam_perbaikan', 'dihapus'])
                ->default('tersedia')->change();
        });
    }
};
