<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_medicine_inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->string('medicine_name', 191);
            $table->string('medicine_code', 50)->nullable()
                  ->comment('Kode obat unik di UKS');
            $table->enum('category', [
                'obat_dalam',
                'obat_luar',
                'vitamin_suplemen',
                'antiseptik',
                'alat_kesehatan',
            ]);
            $table->string('generic_name', 191)->nullable();
            $table->string('unit', 50)
                  ->comment('Satuan: tablet, kapsul, sirup_ml, botol, lembar, unit');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('min_stock_alert', 10, 2)->default(0)
                  ->comment('Batas minimum untuk trigger alert');
            $table->date('expiry_date')->nullable();
            $table->string('storage_location', 191)->nullable()
                  ->comment('Lokasi penyimpanan: lemari A1, dll');
            $table->string('supplier', 191)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->text('dosage_info')->nullable()
                  ->comment('Info dosis umum untuk referensi');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->index(['school_id', 'category']);
            $table->index(['school_id', 'medicine_name']);
            $table->index(['expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_medicine_inventory');
    }
};