<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            // Identitas Dasar
            $table->string('nisn', 20)->unique();
            $table->string('nis', 20)->nullable(); // NIPD
            $table->string('nik', 30)->unique()->nullable();
            $table->string('no_kk', 30)->nullable();

            // Data Pribadi
            $table->string('name', 255);
            $table->enum('gender', ['L', 'P']);
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 50)->nullable();
            $table->enum('special_needs', ['tidak', 'fisik', 'intelektual', 'mental', 'sosial'])->default('tidak');

            // Alamat Lengkap
            $table->text('address')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('hamlet', 100)->nullable(); // Dusun
            $table->char('village_code', 10)->nullable();
            $table->char('district_code', 7)->nullable();
            $table->char('city_code', 4)->nullable();
            $table->char('province_code', 2)->nullable();
            $table->string('postal_code', 10)->nullable();

            // Kontak
            $table->string('phone', 20)->nullable(); // Telepon rumah
            $table->string('mobile_phone', 20)->nullable(); // HP
            $table->string('email', 100)->nullable();

            // Data Tempat Tinggal
            $table->enum('residence_type', ['milik_orangtua', 'sewa', 'asrama', 'panti', 'lainnya'])->default('milik_orangtua');
            $table->enum('transportation', ['jalan_kaki', 'sepeda', 'motor', 'mobil', 'angkutan_umum', 'antar_jemput'])->default('jalan_kaki');
            $table->decimal('distance_to_school', 5, 2)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Data Kesehatan
            $table->integer('height')->nullable(); // cm
            $table->integer('weight')->nullable(); // kg
            $table->integer('head_circumference')->nullable(); // cm
            $table->integer('sibling_count')->default(0);

            // Data Ayah
            $table->string('father_name', 255)->nullable();
            $table->year('father_birth_year')->nullable();
            $table->string('father_education', 50)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->decimal('father_income', 15, 2)->nullable();
            $table->string('father_nik', 30)->nullable();

            // Data Ibu
            $table->string('mother_name', 255)->nullable();
            $table->year('mother_birth_year')->nullable();
            $table->string('mother_education', 50)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->decimal('mother_income', 15, 2)->nullable();
            $table->string('mother_nik', 30)->nullable();

            // Data Wali
            $table->string('guardian_name', 255)->nullable();
            $table->year('guardian_birth_year')->nullable();
            $table->string('guardian_education', 50)->nullable();
            $table->string('guardian_occupation', 100)->nullable();
            $table->decimal('guardian_income', 15, 2)->nullable();
            $table->string('guardian_nik', 30)->nullable();

            // Data Pendaftaran & Sekolah Asal
            $table->integer('child_number')->nullable();
            $table->string('previous_school', 255)->nullable();
            $table->date('entry_date')->nullable();
            $table->integer('entry_grade_level')->nullable();
            $table->string('skhun', 50)->nullable();
            $table->string('ujian_national_number', 50)->nullable();
            $table->string('certificate_number', 50)->nullable();
            $table->string('birth_certificate_number', 50)->nullable();

            // Data PIP/KIP/KPS
            $table->boolean('is_kps_receiver')->default(false);
            $table->string('kps_number', 50)->nullable();
            $table->boolean('is_kip_receiver')->default(false);
            $table->string('kip_number', 50)->nullable();
            $table->string('kip_name', 255)->nullable();
            $table->string('kks_number', 50)->nullable();
            $table->boolean('is_pip_eligible')->default(false);
            $table->text('pip_reason')->nullable();

            // Data Bank
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 255)->nullable();

            // Status Siswa
            $table->enum('status', ['active', 'inactive', 'graduate', 'dropped', 'transfer'])->default('active');
            $table->year('graduation_year')->nullable();
            $table->date('graduation_date')->nullable();

            $table->timestamps();

            // Foreign keys wilayah
            $table->foreign('province_code')->references('code')->on('indonesia_provinces');
            $table->foreign('city_code')->references('code')->on('indonesia_cities');
            $table->foreign('district_code')->references('code')->on('indonesia_districts');
            $table->foreign('village_code')->references('code')->on('indonesia_villages');

            // Index
            $table->index('nisn');
            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
