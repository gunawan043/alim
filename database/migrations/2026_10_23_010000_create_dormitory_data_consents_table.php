<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_data_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id')->index();
            $table->uuid('student_id')->index();
            $table->string('guardian_name', 191);
            $table->string('relationship', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('consent_version', 30);
            $table->timestamp('consented_at');
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'dormitory_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_data_consents');
    }
};
