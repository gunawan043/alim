<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel komunikasi asrama lama sudah dimigrasikan ke humas_announcements
 * dan humas_announcement_images. Hapus agar hanya ada satu sumber data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dormitory_post_images');
        Schema::dropIfExists('dormitory_post_responses');
        Schema::dropIfExists('dormitory_posts');
        Schema::dropIfExists('dormitory_emergency_broadcasts');
    }

    public function down(): void
    {
        // Tabel lama tidak dibuat ulang; data sudah berada di humas_announcements.
    }
};
