<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('building_id');
            $table->string('room_code', 30)->unique();
            $table->string('room_name', 191);
            $table->tinyInteger('floor')->default(1)->comment('Lantai ke-');
            $table->enum('room_type', [
                'kelas', 'laboratorium', 'perpustakaan', 'kantor',
                'aula', 'mushola', 'uks', 'kantin', 'gudang',
                'toilet', 'lapangan', 'lainnya',
            ]);
            $table->decimal('room_area', 8, 2)->nullable()->comment('Luas m²');
            $table->integer('capacity')->nullable()->comment('Kapasitas orang');
            $table->enum('condition', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat',
            ])->default('baik');
            $table->text('facilities')->nullable()
                ->comment('Daftar fasilitas: AC, proyektor, whiteboard, dll');
            $table->tinyInteger('is_bookable')->default(0)
                ->comment('1 = dapat dipesan / dipinjam oleh GTK');
            $table->tinyInteger('booking_requires_approval')->default(1)
                ->comment('1 = perlu persetujuan Admin Sarpras');
            $table->uuid('responsible_user_id')->nullable()
                ->comment('Penanggung jawab ruangan');
            $table->string('photo_path', 255)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->cascadeOnDelete();
            $table->foreign('responsible_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['building_id', 'room_type', 'is_active']);
            $table->index(['school_id', 'is_bookable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_rooms');
    }
};
