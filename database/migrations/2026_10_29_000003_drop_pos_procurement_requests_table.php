<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modul pemesanan lokal lama tidak dipakai; SIPLah memakai API BUMP.
        Schema::dropIfExists('pos_procurement_requests');
    }

    public function down(): void
    {
        // Tidak dibuat ulang.
    }
};
