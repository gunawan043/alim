<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_position_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('current_employment_id')->nullable()->constrained('gtk_employments')->nullOnDelete();
            $table->foreignUuid('proposed_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('proposed_jabatan_text', 150)->nullable();
            $table->foreignUuid('proposed_school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('proposed_work_unit', 100)->nullable();
            $table->text('reason')->nullable();
            $table->enum('proposal_type', ['promosi', 'demosi', 'rotasi', 'mutasi', 'penugasan']);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'cancelled'])->default('submitted');
            $table->foreignUuid('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->string('proposer_role_at_submit', 100)->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->date('tmt')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['proposed_by', 'status']);
            $table->index('proposal_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_position_proposals');
    }
};
