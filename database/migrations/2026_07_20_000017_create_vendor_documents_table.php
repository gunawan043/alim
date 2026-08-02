<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->char('user_id', 36)->nullable();
            $table->string('name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->enum('type', [
                'business_license',
                'npwp',
                'company_registration',
                'tax_certificate',
                'iso_certificate',
                'insurance',
                'bank_reference',
                'product_catalog',
                'price_list',
                'other',
            ]);
            $table->enum('status', [
                'pending',
                'verified',
                'rejected',
                'expired',
                'revoked',
            ])->default('pending');
            $table->date('expiry_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->text('notes')->nullable();
            $table->char('verified_by', 36)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('type');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
