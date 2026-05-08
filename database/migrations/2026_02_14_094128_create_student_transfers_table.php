<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('from_school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('to_school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->date('transfer_date');
            $table->text('reason')->nullable();
            $table->enum('transfer_type', ['masuk', 'keluar', 'pindah'])->default('pindah');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('transfer_date');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};