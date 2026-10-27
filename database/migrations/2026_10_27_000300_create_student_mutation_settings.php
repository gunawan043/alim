<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_mutation_settings')) {
            Schema::create('student_mutation_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->unique()->constrained('schools')->cascadeOnDelete();
                $table->decimal('margin_top', 5, 2)->default(20);
                $table->decimal('margin_right', 5, 2)->default(25);
                $table->decimal('margin_bottom', 5, 2)->default(15);
                $table->decimal('margin_left', 5, 2)->default(25);
                $table->string('font_family', 40)->default('Times New Roman');
                $table->decimal('font_size', 5, 2)->default(12);
                $table->decimal('line_height', 4, 2)->default(1.20);
                $table->string('paper_size', 20)->default('a4');
                $table->boolean('show_kop')->default(true);
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_mutation_settings');
    }
};
