<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seeder unit perpustakaan dipindahkan ke LibraryUnitSeeder agar wajib
     * dijalankan melalui DatabaseSeeder. Migration ini sengaja tidak mengisi data.
     */
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
