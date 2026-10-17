<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure jenis_kelamin column is NOT NULL for consistency.
     * If the column already exists as nullable (from existing migration), make it required.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('gtk_profiles', 'jenis_kelamin')) {
            Schema::table('gtk_profiles', function (Blueprint $table) {
                $table->enum('jenis_kelamin', ['L', 'P'])->after('agama')->nullable()->default(null);
            });
        }
    }

    public function down(): void
    {
        // Keep the column since it may contain user data; just allow nulls again
        Schema::table('gtk_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('gtk_profiles', 'jenis_kelamin')) {
                $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->default(null)->change();
            }
        });
    }
};
