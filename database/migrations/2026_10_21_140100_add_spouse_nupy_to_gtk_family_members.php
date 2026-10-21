<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtk_family_members', function (Blueprint $table) {
            if (! Schema::hasColumn('gtk_family_members', 'spouse_nupy')) {
                $table->string('spouse_nupy', 30)->nullable()->after('linked_gtk_profile_id');
                $table->index('spouse_nupy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gtk_family_members', function (Blueprint $table) {
            if (Schema::hasColumn('gtk_family_members', 'spouse_nupy')) {
                $table->dropIndex(['spouse_nupy']);
                $table->dropColumn('spouse_nupy');
            }
        });
    }
};
