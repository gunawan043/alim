<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_lands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->string('land_name', 191);
            $table->string('certificate_number', 100)->nullable();
            $table->enum('certificate_type', ['shm', 'shgb', 'hp', 'wakaf', 'lainnya'])
                  ->nullable();
            $table->decimal('land_area', 10, 2)->nullable()->comment('Luas dalam m²');
            $table->text('address')->nullable();
            $table->char('province_code', 2)->nullable();
            $table->char('city_code', 4)->nullable();
            $table->char('district_code', 7)->nullable();
            $table->char('village_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->year('acquisition_year')->nullable();
            $table->decimal('acquisition_price', 15, 2)->nullable();
            $table->enum('acquisition_source', [
                'pembelian', 'hibah', 'wakaf', 'pemerintah', 'lainnya',
            ])->nullable();
            $table->enum('land_use', [
                'bangunan', 'lapangan', 'kebun', 'parkir', 'lainnya',
            ])->default('bangunan');
            $table->enum('status', ['aktif', 'sengketa', 'dijual', 'lainnya'])
                  ->default('aktif');
            $table->string('document_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('province_code')->references('code')->on('indonesia_provinces')->nullOnDelete();
            $table->foreign('city_code')->references('code')->on('indonesia_cities')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['work_unit_id', 'status']);
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('asset_lands'); }
};
