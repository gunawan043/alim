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
        Schema::create('sidebar_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('menu_key')->index(); // unique key per menu item, e.g. 'gtk.guru', 'gtk.tendik', 'gtk'
            $table->json('allowed_roles');        // array of role names, e.g. ['Admin Tata Usaha', 'Wakil Kepala Sekolah']
            $table->timestamps();

            $table->unique('menu_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidebar_accesses');
    }
};
