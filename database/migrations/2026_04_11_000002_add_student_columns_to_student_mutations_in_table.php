<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_mutations_in', function (Blueprint $table) {
            // Kontak & email
            if (! Schema::hasColumn('student_mutations_in', 'phone')) {
                $table->string('phone', 30)->nullable()->after('parent_phone');
            }
            if (! Schema::hasColumn('student_mutations_in', 'mobile_phone')) {
                $table->string('mobile_phone', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('student_mutations_in', 'email')) {
                $table->string('email', 100)->nullable()->after('mobile_phone');
            }

            // Tempat tinggal
            if (! Schema::hasColumn('student_mutations_in', 'residence_type')) {
                $table->string('residence_type', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('student_mutations_in', 'transportation')) {
                $table->string('transportation', 50)->nullable()->after('residence_type');
            }
            if (! Schema::hasColumn('student_mutations_in', 'distance_to_school')) {
                $table->decimal('distance_to_school', 5, 2)->nullable()->after('transportation');
            }

            // Kesehatan
            if (! Schema::hasColumn('student_mutations_in', 'height')) {
                $table->integer('height')->nullable()->after('distance_to_school');
            }
            if (! Schema::hasColumn('student_mutations_in', 'weight')) {
                $table->integer('weight')->nullable()->after('height');
            }
            if (! Schema::hasColumn('student_mutations_in', 'head_circumference')) {
                $table->integer('head_circumference')->nullable()->after('weight');
            }
            if (! Schema::hasColumn('student_mutations_in', 'sibling_count')) {
                $table->integer('sibling_count')->default(0)->after('head_circumference');
            }

            // Pendaftaran
            if (! Schema::hasColumn('student_mutations_in', 'child_number')) {
                $table->integer('child_number')->nullable()->after('sibling_count');
            }
            if (! Schema::hasColumn('student_mutations_in', 'entry_grade_level')) {
                $table->integer('entry_grade_level')->nullable()->after('child_number');
            }
            if (! Schema::hasColumn('student_mutations_in', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('entry_grade_level');
            }
            if (! Schema::hasColumn('student_mutations_in', 'skhun')) {
                $table->string('skhun', 50)->nullable()->after('entry_date');
            }
            if (! Schema::hasColumn('student_mutations_in', 'ujian_national_number')) {
                $table->string('ujian_national_number', 50)->nullable()->after('skhun');
            }
            if (! Schema::hasColumn('student_mutations_in', 'certificate_number')) {
                $table->string('certificate_number', 50)->nullable()->after('ujian_national_number');
            }
            if (! Schema::hasColumn('student_mutations_in', 'birth_certificate_number')) {
                $table->string('birth_certificate_number', 50)->nullable()->after('certificate_number');
            }

            // Bantuan sosial
            if (! Schema::hasColumn('student_mutations_in', 'is_kps_receiver')) {
                $table->boolean('is_kps_receiver')->default(false)->after('birth_certificate_number');
            }
            if (! Schema::hasColumn('student_mutations_in', 'kps_number')) {
                $table->string('kps_number', 50)->nullable()->after('is_kps_receiver');
            }
            if (! Schema::hasColumn('student_mutations_in', 'is_kip_receiver')) {
                $table->boolean('is_kip_receiver')->default(false)->after('kps_number');
            }
            if (! Schema::hasColumn('student_mutations_in', 'kip_number')) {
                $table->string('kip_number', 50)->nullable()->after('is_kip_receiver');
            }
            if (! Schema::hasColumn('student_mutations_in', 'kip_name')) {
                $table->string('kip_name', 255)->nullable()->after('kip_number');
            }
            if (! Schema::hasColumn('student_mutations_in', 'is_pip_eligible')) {
                $table->boolean('is_pip_eligible')->default(false)->after('kip_name');
            }
            if (! Schema::hasColumn('student_mutations_in', 'kks_number')) {
                $table->string('kks_number', 50)->nullable()->after('is_pip_eligible');
            }
            if (! Schema::hasColumn('student_mutations_in', 'pip_reason')) {
                $table->text('pip_reason')->nullable()->after('kks_number');
            }

            // Status & kelulusan
            if (! Schema::hasColumn('student_mutations_in', 'status')) {
                $table->string('status', 20)->default('active')->after('pip_reason');
            }
            if (! Schema::hasColumn('student_mutations_in', 'graduation_year')) {
                $table->year('graduation_year')->nullable()->after('status');
            }
            if (! Schema::hasColumn('student_mutations_in', 'graduation_date')) {
                $table->date('graduation_date')->nullable()->after('graduation_year');
            }

            // Bank
            if (! Schema::hasColumn('student_mutations_in', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('graduation_date');
            }
            if (! Schema::hasColumn('student_mutations_in', 'bank_cabang')) {
                $table->string('bank_cabang', 100)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('student_mutations_in', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('bank_cabang');
            }
            if (! Schema::hasColumn('student_mutations_in', 'bank_account_name')) {
                $table->string('bank_account_name', 100)->nullable()->after('bank_account_number');
            }

            // Data pribadi tambahan
            if (! Schema::hasColumn('student_mutations_in', 'religion')) {
                $table->string('religion', 50)->nullable()->after('student_current_class');
            }
            if (! Schema::hasColumn('student_mutations_in', 'special_needs')) {
                $table->string('special_needs', 50)->default('tidak')->after('religion');
            }
            if (! Schema::hasColumn('student_mutations_in', 'no_kk')) {
                $table->string('no_kk', 30)->nullable()->after('special_needs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations_in', function (Blueprint $table) {
            $columns = [
                'phone', 'mobile_phone', 'email',
                'residence_type', 'transportation', 'distance_to_school',
                'height', 'weight', 'head_circumference', 'sibling_count',
                'child_number', 'entry_grade_level', 'entry_date',
                'skhun', 'ujian_national_number', 'certificate_number', 'birth_certificate_number',
                'is_kps_receiver', 'kps_number',
                'is_kip_receiver', 'kip_number', 'kip_name',
                'is_pip_eligible', 'kks_number', 'pip_reason',
                'status', 'graduation_year', 'graduation_date',
                'bank_name', 'bank_cabang', 'bank_account_number', 'bank_account_name',
                'religion', 'special_needs', 'no_kk',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('student_mutations_in', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
