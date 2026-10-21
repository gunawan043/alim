<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('humas_complaints')) {
            Schema::create('humas_complaints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('category', 50)->default('umum');
                $table->string('subject');
                $table->text('message');
                $table->string('status', 20)->default('open');
                $table->text('response')->nullable();
                $table->foreignUuid('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('handled_at')->nullable();
                $table->timestamps();
                $table->index(['school_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('humas_complaints');
    }
};
