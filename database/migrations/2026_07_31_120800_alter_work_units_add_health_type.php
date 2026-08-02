<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Change type column from ENUM to VARCHAR to allow 'Pelayanan Kesehatan' and other types
        if (\Schema::hasColumn('work_units', 'type')) {
            \DB::statement('ALTER TABLE work_units MODIFY COLUMN type VARCHAR(100) NULL');
        }
    }

    public function down(): void
    {
        // Revert back to original enum-like values
        $types = "'Unsur Pimpinan','Unit Akademik','Unit Penunjang Akademik','Unit Administrasi','Unit Pelayanan','Unit Humas Publikasi'";
        if (\Schema::hasColumn('work_units', 'type')) {
            \DB::statement("ALTER TABLE work_units MODIFY COLUMN type ENUM($types) NULL DEFAULT NULL");
        }
    }
};
