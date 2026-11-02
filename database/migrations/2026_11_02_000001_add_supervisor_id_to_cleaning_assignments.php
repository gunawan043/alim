<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Kolom supervisor_id dipakai modul penugasan kebersihan, tetapi migrasi lama
 * berjalan sebelum tabel cleaning_assignments dibuat sehingga pada instalasi
 * yang dibuat ulang kolomnya tidak pernah ada. Migrasi ini menutup celah itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cleaning_assignments') || Schema::hasColumn('cleaning_assignments', 'supervisor_id')) {
            return;
        }

        Schema::table('cleaning_assignments', function (Blueprint $table) {
            $table->uuid('supervisor_id')->nullable()->after('backup_user_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('cleaning_assignments', function (Blueprint $table) {
                $table->foreign('supervisor_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cleaning_assignments') || ! Schema::hasColumn('cleaning_assignments', 'supervisor_id')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('cleaning_assignments', function (Blueprint $table) {
                $table->dropForeign(['supervisor_id']);
            });
        }

        Schema::table('cleaning_assignments', function (Blueprint $table) {
            $table->dropColumn('supervisor_id');
        });
    }
};
