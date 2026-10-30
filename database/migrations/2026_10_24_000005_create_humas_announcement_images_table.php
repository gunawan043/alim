<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('humas_announcement_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('humas_announcement_id')
                ->constrained('humas_announcements')
                ->cascadeOnDelete();
            $table->string('path', 500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['humas_announcement_id', 'sort_order']);
        });

        // Migrasikan banner tunggal lama menjadi satu gambar carousel.
        DB::table('humas_announcements')
            ->whereNotNull('banner_path')
            ->where('banner_path', '!=', '')
            ->orderBy('id')
            ->each(function ($announcement) {
                DB::table('humas_announcement_images')->insert([
                    'id' => (string) Str::uuid(),
                    'humas_announcement_id' => $announcement->id,
                    'path' => $announcement->banner_path,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('humas_announcement_images');
    }
};
