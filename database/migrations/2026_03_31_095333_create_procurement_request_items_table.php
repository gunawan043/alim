<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('academic_year_id')->nullable();
            $table->string('request_number', 50)->unique()
                ->comment('Generate otomatis, misal: PBJ/2025/001');
            $table->uuid('requested_by');
            $table->date('request_date');
            $table->enum('urgency', ['rendah', 'sedang', 'tinggi', 'mendesak'])
                ->default('sedang');
            $table->decimal('total_estimated_price', 15, 2)->nullable()
                ->comment('Dihitung otomatis dari sum procurement_request_items');
            $table->enum('status', [
                'draft', 'submitted', 'review', 'approved',
                'rejected', 'purchasing', 'received', 'cancelled',
            ])->default('draft');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['work_unit_id', 'status', 'request_date']);
            $table->index(['requested_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_requests');
    }
};
