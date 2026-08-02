<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gtk_work_unit_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // GTK
            $table->foreignId('from_work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignId('to_work_unit_id')->constrained('work_units')->cascadeOnDelete();

            $table->string('jabatan')->nullable();

            $table->enum('action', [
                'ASSIGN',
                'TRANSFER',
                'REMOVE',
            ]);

            $table->text('reason')->nullable();

            // AUDIT
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_work_unit_histories');
    }
};
