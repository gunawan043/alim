<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->string('facility_name', 191);
            $table->enum('facility_type', [
                'puskesmas',
                'rumah_sakit',
                'klinik',
                'dokter_praktik',
                'rs_psychologist',
                'posyandu',
            ]);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->tinyInteger('is_available_24h')->default(0)
                ->comment('1 = buka 24 jam');
            $table->text('services')->nullable()
                ->comment('Layanan yang tersedia, JSON array');
            $table->string('operating_hours', 100)->nullable()
                ->comment('Jam buka, misal: 08.00-16.00');
            $table->text('notes')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->index(['school_id', 'facility_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_referrals');
    }
};
