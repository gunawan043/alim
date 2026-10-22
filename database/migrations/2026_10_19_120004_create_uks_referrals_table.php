<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('uks_patients')->cascadeOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('facility_id')->nullable()->constrained('facility_referrals')->nullOnDelete();

            $table->string('referral_no', 50)->nullable();
            $table->string('facility_name', 191);
            $table->string('facility_type', 30)->nullable();
            $table->timestamp('referred_at');
            $table->string('urgency', 20)->default('rutin');
            $table->text('diagnosis')->nullable();
            $table->text('reason');
            $table->string('transport', 50)->nullable();
            $table->string('accompanied_by', 191)->nullable();

            $table->string('status', 20)->default('dirujuk');
            $table->text('outcome')->nullable();
            $table->foreignUuid('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status'], 'uks_referrals_patient_status_idx');
            $table->index(['school_id', 'referred_at'], 'uks_referrals_school_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_referrals');
    }
};
