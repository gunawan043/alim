<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('borrower_id');
            $table->text('purpose');
            $table->date('loan_date');
            $table->time('loan_time')->nullable();
            $table->date('expected_return_date');
            $table->date('actual_return_date')->nullable();
            $table->time('actual_return_time')->nullable();
            $table->enum('condition_on_loan', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat',
            ])->default('baik');
            $table->enum('condition_on_return', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang',
            ])->nullable();
            $table->enum('status', [
                'pending', 'approved', 'dipinjam', 'dikembalikan',
                'terlambat', 'hilang', 'dibatalkan',
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('returned_to')->nullable()
                ->comment('GTK yang menerima pengembalian aset');
            $table->text('damage_notes')->nullable();
            $table->uuid('related_agenda_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('borrower_id')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('returned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('related_agenda_id')->references('id')->on('agendas')->nullOnDelete();

            $table->index(['asset_id', 'status']);
            $table->index(['borrower_id', 'status']);
            $table->index(['status', 'expected_return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
    }
};
