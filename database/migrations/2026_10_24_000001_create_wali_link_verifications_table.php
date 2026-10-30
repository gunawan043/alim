<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali_link_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->string('relationship', 20);
            $table->string('method', 20)->default('identity');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_link_verifications');
    }
};
