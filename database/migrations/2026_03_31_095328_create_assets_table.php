<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('asset_category_id');
            $table->uuid('room_id')->nullable()->comment('Lokasi aset saat ini');

            // --- IDENTITAS ---
            $table->string('asset_code', 50)->unique()
                ->comment('Kode inventaris — jadi konten QR Code label aset');
            $table->string('asset_name', 191);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->text('specification')->nullable();

            // --- PEROLEHAN ---
            $table->date('acquisition_date')->nullable();
            $table->year('acquisition_year')->nullable();
            $table->decimal('acquisition_price', 15, 2)->nullable();
            $table->enum('acquisition_source', [
                'pembelian', 'hibah', 'sumbangan',
                'pengadaan_bos', 'bantuan_pemerintah', 'lainnya',
            ])->default('pembelian');
            $table->string('funding_source', 100)->nullable()
                ->comment('BOS, BOSDA, Dana Ponpes, Donasi, dll');
            $table->string('supplier_name', 191)->nullable();
            $table->string('purchase_document_path', 255)->nullable();

            // --- KONDISI & STATUS ---
            $table->enum('condition', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang', 'dihapus',
            ])->default('baik');
            $table->enum('status', [
                'tersedia', 'dipinjam', 'dalam_perbaikan', 'dihapus',
            ])->default('tersedia');
            $table->tinyInteger('is_bookable')->default(1)
                ->comment('1 = bisa dipinjam GTK');

            // --- NILAI & PENYUSUTAN ---
            $table->decimal('current_value', 15, 2)->nullable()
                ->comment('Nilai buku saat ini setelah penyusutan');
            $table->decimal('depreciation_per_year', 15, 2)->nullable();
            $table->date('last_valuation_date')->nullable();

            // --- AUDIT ---
            $table->date('last_audit_date')->nullable();
            $table->uuid('last_audit_by')->nullable();
            $table->date('last_condition_update')->nullable();
            $table->timestamp('qr_generated_at')->nullable()
                ->comment('Kapan label QR terakhir digenerate / dicetak');

            $table->string('photo_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('asset_category_id')->references('id')->on('asset_categories');
            $table->foreign('room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('last_audit_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['work_unit_id', 'condition', 'status']);
            $table->index(['school_id', 'asset_category_id']);
            $table->index(['room_id', 'condition']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
