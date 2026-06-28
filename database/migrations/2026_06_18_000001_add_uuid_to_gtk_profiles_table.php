<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add uuid column to gtk_profiles for external recruitment mapping.
     * GtkProfile.id is the primary key (UUID from HasUuid trait).
     * GtkProfile.uuid is a nullable column used to store the external
     * recruitment_abuhurairah.id UUID during conversion.
     */
    public function up(): void
    {
        Schema::table('gtk_profiles', function (Blueprint $table) {
            $table->char('uuid', 36)
                ->nullable()
                ->after('id')
                ->unique();
        });
    }

    /**
     * Drop the uuid column added during up().
     */
    public function down(): void
    {
        Schema::table('gtk_profiles', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
