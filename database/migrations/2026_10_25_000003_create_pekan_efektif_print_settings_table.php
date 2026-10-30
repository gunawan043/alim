<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pekan_efektif_print_settings')) {
            return;
        }

        Schema::create('pekan_efektif_print_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->decimal('margin_top', 5, 2)->default(10);
            $table->decimal('margin_right', 5, 2)->default(10);
            $table->decimal('margin_bottom', 5, 2)->default(12);
            $table->decimal('margin_left', 5, 2)->default(12);
            $table->decimal('font_size', 5, 2)->default(10);
            $table->decimal('title_font_size', 5, 2)->default(14);
            $table->decimal('table_font_size', 5, 2)->default(9);
            $table->decimal('info_font_size', 5, 2)->default(10);
            $table->decimal('footer_font_size', 5, 2)->default(8);
            $table->decimal('line_height', 4, 2)->default(1.2);
            $table->string('paper_size', 20)->default('a4');
            $table->boolean('show_kop')->default(true);
            $table->boolean('show_signature')->default(true);
            $table->string('city', 100)->nullable();
            $table->string('guru_nama', 150)->nullable();
            $table->string('guru_nip', 50)->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekan_efektif_print_settings');
    }
};
