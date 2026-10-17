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
        Schema::table('recruitment_profiles', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('id'); // ID dari recruitment.abuhurairah.id
            $table->text('foto_url_external')->nullable(); // URL foto dari external system
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_profiles', function (Blueprint $table) {
            $table->dropColumn('external_id');
            $table->dropColumn('foto_url_external');
        });
    }
};
