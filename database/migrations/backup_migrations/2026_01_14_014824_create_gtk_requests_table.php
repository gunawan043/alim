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
        Schema::create('gtk_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_unit_id')
                ->constrained('work_units')
                ->cascadeOnDelete();

            $table->foreignId('requested_by')
                ->constrained('users');

            $table->string('jabatan');
            $table->unsignedTinyInteger('jumlah');
            $table->text('alasan');

            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
            ])->default('draft');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_requests');
    }
};
