<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_gatepasses')) {
            Schema::create('dormitory_gatepasses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->enum('type', ['keluar', 'masuk']);
                $table->string('purpose')->nullable();
                $table->string('gate')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('scanned_by')->nullable()->constrained('users', 'id')->nullOnDelete();
                $table->timestamp('scanned_at');
                $table->timestamps();

                $table->index(['dormitory_id', 'scanned_at']);
                $table->index(['student_id', 'scanned_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_gatepasses');
    }
};
