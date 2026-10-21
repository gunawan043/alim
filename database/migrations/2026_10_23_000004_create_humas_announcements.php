<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('humas_announcements')) {
            Schema::create('humas_announcements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('title');
                $table->string('slug')->nullable();
                $table->string('category', 50)->default('umum');
                $table->string('audience', 20)->default('all');
                $table->text('excerpt')->nullable();
                $table->longText('content');
                $table->string('status', 20)->default('draft');
                $table->boolean('is_pinned')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'audience', 'published_at'], 'humas_announcement_feed_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('humas_announcements');
    }
};
