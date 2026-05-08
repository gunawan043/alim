<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_unit_id')->constrained('work_units')->cascadeOnDelete();
            
            $table->string('npsn', 20)->unique();
            $table->string('nss', 30)->nullable();
            $table->string('name', 255);
            $table->text('address')->nullable();
            
            // Wilayah
            $table->char('province_code', 2)->nullable();
            $table->char('city_code', 4)->nullable();
            $table->char('district_code', 7)->nullable();
            $table->char('village_code', 10)->nullable();
            $table->string('postal_code', 10)->nullable();
            
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 100)->nullable();
            
            $table->enum('school_level', ['sd', 'smp', 'sma', 'smk'])->nullable();
            $table->enum('school_status', ['negeri', 'swasta'])->default('negeri');
            $table->string('accreditation', 10)->nullable();
            $table->year('accreditation_year')->nullable();
            
            $table->string('principal_name', 255)->nullable();
            $table->string('principal_nip', 30)->nullable();
            $table->enum('operational_hours', ['pagi', 'siang', 'full_day'])->default('pagi');
            
            $table->date('established_date')->nullable();
            $table->string('established_decree', 100)->nullable();
            $table->decimal('land_area', 10, 2)->nullable();
            $table->decimal('building_area', 10, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->string('logo_path', 255)->nullable();
            
            $table->timestamps();
            
            // Foreign keys ke tabel wilayah
            $table->foreign('province_code')->references('code')->on('indonesia_provinces');
            $table->foreign('city_code')->references('code')->on('indonesia_cities');
            $table->foreign('district_code')->references('code')->on('indonesia_districts');
            $table->foreign('village_code')->references('code')->on('indonesia_villages');
            
            // Index
            $table->index('npsn');
            $table->index('work_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};