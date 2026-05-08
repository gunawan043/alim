<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_buildings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('land_id')->nullable()->comment('Bangunan berdiri di lahan mana');
            $table->string('building_code', 30)->unique();
            $table->string('building_name', 191);
            $table->enum('building_type', [
                'kelas', 'kantor', 'laboratorium', 'perpustakaan',
                'masjid', 'mushola', 'asrama', 'aula', 'kantin',
                'uks', 'koperasi', 'gudang', 'toilet', 'lapangan', 'lainnya',
            ]);
            $table->tinyInteger('total_floors')->default(1);
            $table->decimal('building_area', 10, 2)->nullable()->comment('Luas m²');
            $table->year('build_year')->nullable();
            $table->year('renovation_year')->nullable();
            $table->enum('structure_condition', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat',
            ])->default('baik');
            $table->enum('ownership_status', [
                'milik_sendiri', 'sewa', 'pinjam', 'kerjasama',
            ])->default('milik_sendiri');
            $table->string('imb_number', 100)->nullable();
            $table->date('imb_date')->nullable();
            $table->integer('total_rooms')->default(0);
            $table->text('notes')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('document_path', 255)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('land_id')->references('id')->on('asset_lands')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['work_unit_id', 'building_type', 'is_active']);
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('asset_buildings'); }
};
