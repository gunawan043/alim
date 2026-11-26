<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku nilai harus dapat ditelusuri ke SK yang menerbitkannya.
 *
 * Sebelumnya hubungan hanya lewat teaching_assignments, dan kolom
 * teacher_admin_books.teaching_id pun tidak pernah terisi (ia menunjuk tabel
 * legacy `teachings`). Akibatnya data nilai tidak punya ikatan eksplisit ke
 * periode SK, sehingga semester mudah tertukar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_admin_books') || Schema::hasColumn('teacher_admin_books', 'decree_id')) {
            return;
        }

        Schema::table('teacher_admin_books', function (Blueprint $table) {
            $table->uuid('decree_id')->nullable()->after('academic_year_id');
            $table->index('decree_id', 'teacher_admin_books_decree_id_index');
        });

        // Beberapa driver (SQLite) tidak mendukung penambahan FK setelah tabel
        // dibuat, jadi batas integritas dijaga di lapisan aplikasi.
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_admin_books') || ! Schema::hasColumn('teacher_admin_books', 'decree_id')) {
            return;
        }

        Schema::table('teacher_admin_books', function (Blueprint $table) {
            $table->dropIndex('teacher_admin_books_decree_id_index');
            $table->dropColumn('decree_id');
        });
    }
};
