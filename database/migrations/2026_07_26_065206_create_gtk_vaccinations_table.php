<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_vaccinations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('administered_by')->nullable()->constrained('users');

            $table->string('vaccine_name'); // Covid, Hepatitis B, Tetanus, Influenza, Lainnya
            $table->timestamp('given_at');
            $table->string('batch_number')->nullable();
            $table->timestamp('next_due_date')->nullable()->comment('Jadwal vaksinasi berikutnya');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'given_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_vaccinations');
    }
};
