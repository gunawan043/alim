<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->expandPermitTypeEnum();
        $this->addPermitPolicyColumns();
        $this->addVisitPolicyColumns();
        $this->seedPolicyPermitTypes();
        $this->createVisitSchedulesTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_visit_schedules');

        if (Schema::hasTable('dormitory_visit_logs')) {
            Schema::table('dormitory_visit_logs', function (Blueprint $table) {
                $columns = [
                    'visitor_count', 'visitor_gender', 'identity_verified_at', 'identity_verified_by',
                    'card_verified_at', 'card_verified_by', 'rules_acknowledged_at',
                    'schedule_exception_reason', 'schedule_exception_approved_by', 'schedule_exception_approved_at',
                    'linked_permit_id',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('dormitory_visit_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('dormitory_permits')) {
            Schema::table('dormitory_permits', function (Blueprint $table) {
                $columns = [
                    'guardian_approval_status', 'guardian_approved_at', 'guardian_approval_channel',
                    'guardian_approval_note', 'guardian_approval_evidence_path',
                    'card_issued_at', 'card_issued_by', 'card_returned_at', 'card_returned_by',
                    'card_status', 'exit_checklist', 'return_checklist',
                    'late_return_reason', 'late_return_confirmed_by', 'late_return_confirmed_at',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('dormitory_permits', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function expandPermitTypeEnum(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('dormitory_permits')) {
            DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN permit_type ENUM(
                'pulang', 'keluar_kota', 'berobat', 'sakit', 'keperluan_keluarga',
                'musibah', 'walimah_keluarga', 'haji_umrah', 'wisuda_keluarga',
                'keperluan_penting', 'darurat', 'lainnya'
            ) NOT NULL");
        }
    }

    private function addPermitPolicyColumns(): void
    {
        if (! Schema::hasTable('dormitory_permits')) {
            return;
        }

        $columns = [
            'guardian_approval_status' => fn (Blueprint $table) => $table->string('guardian_approval_status', 20)->nullable()->after('purpose'),
            'guardian_approved_at' => fn (Blueprint $table) => $table->timestamp('guardian_approved_at')->nullable()->after('guardian_approval_status'),
            'guardian_approval_channel' => fn (Blueprint $table) => $table->string('guardian_approval_channel', 20)->nullable()->after('guardian_approved_at'),
            'guardian_approval_note' => fn (Blueprint $table) => $table->text('guardian_approval_note')->nullable()->after('guardian_approval_channel'),
            'guardian_approval_evidence_path' => fn (Blueprint $table) => $table->string('guardian_approval_evidence_path', 255)->nullable()->after('guardian_approval_note'),
            'card_issued_at' => fn (Blueprint $table) => $table->timestamp('card_issued_at')->nullable()->after('guardian_approval_evidence_path'),
            'card_issued_by' => fn (Blueprint $table) => $table->uuid('card_issued_by')->nullable()->after('card_issued_at'),
            'card_returned_at' => fn (Blueprint $table) => $table->timestamp('card_returned_at')->nullable()->after('card_issued_by'),
            'card_returned_by' => fn (Blueprint $table) => $table->uuid('card_returned_by')->nullable()->after('card_returned_at'),
            'card_status' => fn (Blueprint $table) => $table->string('card_status', 20)->default('not_issued')->after('card_returned_by'),
            'exit_checklist' => fn (Blueprint $table) => $table->json('exit_checklist')->nullable()->after('card_status'),
            'return_checklist' => fn (Blueprint $table) => $table->json('return_checklist')->nullable()->after('exit_checklist'),
            'late_return_reason' => fn (Blueprint $table) => $table->text('late_return_reason')->nullable()->after('return_checklist'),
            'late_return_confirmed_by' => fn (Blueprint $table) => $table->uuid('late_return_confirmed_by')->nullable()->after('late_return_reason'),
            'late_return_confirmed_at' => fn (Blueprint $table) => $table->timestamp('late_return_confirmed_at')->nullable()->after('late_return_confirmed_by'),
        ];

        Schema::table('dormitory_permits', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('dormitory_permits', $column)) {
                    $definition($table);
                }
            }
        });
    }

    private function addVisitPolicyColumns(): void
    {
        if (! Schema::hasTable('dormitory_visit_logs')) {
            return;
        }

        $columns = [
            'visitor_count' => fn (Blueprint $table) => $table->unsignedInteger('visitor_count')->default(1)->after('visitor_phone'),
            'visitor_gender' => fn (Blueprint $table) => $table->string('visitor_gender', 10)->nullable()->after('visitor_count'),
            'identity_verified_at' => fn (Blueprint $table) => $table->timestamp('identity_verified_at')->nullable()->after('visitor_gender'),
            'identity_verified_by' => fn (Blueprint $table) => $table->uuid('identity_verified_by')->nullable()->after('identity_verified_at'),
            'card_verified_at' => fn (Blueprint $table) => $table->timestamp('card_verified_at')->nullable()->after('identity_verified_by'),
            'card_verified_by' => fn (Blueprint $table) => $table->uuid('card_verified_by')->nullable()->after('card_verified_at'),
            'rules_acknowledged_at' => fn (Blueprint $table) => $table->timestamp('rules_acknowledged_at')->nullable()->after('card_verified_by'),
            'schedule_exception_reason' => fn (Blueprint $table) => $table->text('schedule_exception_reason')->nullable()->after('rules_acknowledged_at'),
            'schedule_exception_approved_by' => fn (Blueprint $table) => $table->uuid('schedule_exception_approved_by')->nullable()->after('schedule_exception_reason'),
            'schedule_exception_approved_at' => fn (Blueprint $table) => $table->timestamp('schedule_exception_approved_at')->nullable()->after('schedule_exception_approved_by'),
            'linked_permit_id' => fn (Blueprint $table) => $table->uuid('linked_permit_id')->nullable()->after('schedule_exception_approved_at'),
        ];

        Schema::table('dormitory_visit_logs', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('dormitory_visit_logs', $column)) {
                    $definition($table);
                }
            }
        });
    }

    private function seedPolicyPermitTypes(): void
    {
        if (! Schema::hasTable('permit_types')) {
            return;
        }

        $types = [
            ['code' => 'pulang', 'label' => 'Izin Pulang', 'category' => 'default', 'icon' => 'ri-home-4-line', 'color' => 'primary', 'sort_order' => 10],
            ['code' => 'musibah', 'label' => 'Izin Musibah', 'category' => 'special', 'icon' => 'ri-emotion-sad-line', 'color' => 'danger', 'sort_order' => 20],
            ['code' => 'walimah_keluarga', 'label' => 'Walimah Orang Tua/Saudara Kandung', 'category' => 'special', 'icon' => 'ri-heart-2-line', 'color' => 'warning', 'sort_order' => 30],
            ['code' => 'haji_umrah', 'label' => 'Izin Haji/Umrah', 'category' => 'special', 'icon' => 'ri-flight-takeoff-line', 'color' => 'info', 'sort_order' => 40],
            ['code' => 'wisuda_keluarga', 'label' => 'Wisuda Orang Tua/Saudara Kandung', 'category' => 'special', 'icon' => 'ri-graduation-cap-line', 'color' => 'info', 'sort_order' => 50],
            ['code' => 'sakit', 'label' => 'Izin Sakit', 'category' => 'special', 'icon' => 'ri-hospital-line', 'color' => 'danger', 'sort_order' => 60],
            ['code' => 'berobat', 'label' => 'Izin Berobat', 'category' => 'special', 'icon' => 'ri-stethoscope-line', 'color' => 'success', 'sort_order' => 70],
            ['code' => 'keperluan_penting', 'label' => 'Keperluan Penting Lainnya', 'category' => 'special', 'icon' => 'ri-more-line', 'color' => 'secondary', 'sort_order' => 80],
            ['code' => 'darurat', 'label' => 'Izin Darurat', 'category' => 'emergency', 'icon' => 'ri-alarm-warning-line', 'color' => 'danger', 'sort_order' => 90],
        ];

        foreach ($types as $type) {
            if (! DB::table('permit_types')->where('code', $type['code'])->exists()) {
                DB::table('permit_types')->insert(array_merge($type, [
                    'id' => (string) Str::uuid(),
                    'description' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function createVisitSchedulesTable(): void
    {
        if (Schema::hasTable('dormitory_visit_schedules')) {
            return;
        }

        Schema::create('dormitory_visit_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id')->nullable();
            $table->enum('gender', ['putra', 'putri'])->nullable();
            $table->unsignedTinyInteger('week_of_month');
            $table->unsignedTinyInteger('day_of_week')->default(0)->comment('0=Ahad, 1=Senin, ... 6=Sabtu');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('dormitory_id')->references('id')->on('dormitories')->nullOnDelete();
            $table->index(['gender', 'week_of_month', 'day_of_week']);
        });
    }
};
