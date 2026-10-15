<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template LJK berversi.
 *
 * Geometri (posisi marker, kotak jawaban, QR, area tulis) disimpan dalam
 * milimeter pada kolom `geometry`. Baris template bersifat IMMUTABLE:
 * perubahan tata letak wajib membuat versi baru (code + version unik),
 * sehingga hasil pemindaian lama tetap tertelusur ke template yang benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_templates')) {
            return;
        }

        Schema::create('ljk_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64);
            $table->unsignedInteger('version')->default(1);
            $table->string('name', 120);
            $table->string('paper', 12)->default('A4');
            $table->string('orientation', 12)->default('portrait');
            $table->unsignedTinyInteger('columns')->default(2);
            $table->decimal('box_width_mm', 5, 2)->default(6.20);
            $table->decimal('box_height_mm', 5, 2)->default(4.80);
            $table->decimal('row_height_mm', 5, 2)->default(8.20);
            $table->json('geometry');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->unique(['code', 'version'], 'ljk_templates_code_version_unique');
            $table->index('is_active', 'ljk_templates_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_templates');
    }
};
