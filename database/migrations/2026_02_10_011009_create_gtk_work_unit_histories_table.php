<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_work_unit_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('from_work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignUuid('to_work_unit_id')->constrained('work_units')->cascadeOnDelete();

            $table->string('jabatan')->nullable();
            $table->enum('action', ['ASSIGN', 'TRANSFER', 'REMOVE']);
            $table->text('reason')->nullable();

            // AUDIT
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_work_unit_histories');
    }
};
