<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Definisi kolom Penilaian Asesmen Formatif per aspek (label sub-kolom). */
    public function up(): void
    {
        if (! Schema::hasTable('teacher_admin_books') || Schema::hasColumn('teacher_admin_books', 'formatif_columns')) {
            return;
        }

        Schema::table('teacher_admin_books', function (Blueprint $table) {
            $table->json('formatif_columns')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_admin_books') || ! Schema::hasColumn('teacher_admin_books', 'formatif_columns')) {
            return;
        }

        Schema::table('teacher_admin_books', function (Blueprint $table) {
            $table->dropColumn('formatif_columns');
        });
    }
};
