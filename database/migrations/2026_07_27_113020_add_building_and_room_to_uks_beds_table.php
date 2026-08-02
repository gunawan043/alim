<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uks_beds', function (Blueprint $table) {
            // Gedung UKS (cth. "UKS Putra", "UKS Putri")
            $table->string('building')->nullable()->after('dormitory_id');
            // Ruangan (cth. "Ruang A")
            $table->string('room')->nullable()->after('building');
        });
    }

    public function down(): void
    {
        Schema::table('uks_beds', function (Blueprint $table) {
            $table->dropColumn(['building', 'room']);
        });
    }
};
