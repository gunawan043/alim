<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_lifecycle_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event', 64);
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['school_id', 'event', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_lifecycle_audits');
    }
};
