<?php

namespace Tests\Feature;

use App\Exports\DormitoryPermitsExport;
use App\Models\BoardingPolicy;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Boarding\VisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Penyempurnaan asrama: otorisasi kuota (Kepala/Wakil Asrama),
 * akurasi hitungan kuota kunjungan per bulan, dan export Excel.
 */
class AsramaQuotaGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormitory;

    private User $kepala;

    private User $wakil;

    private User $staf;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->seedFixture();
    }

    public function test_hanya_kepala_dan_wakil_kepala_asrama_yang_dapat_mengubah_kuota(): void
    {
        $access = app(DormitoryPolicyAccess::class);

        $this->assertTrue($access->canManageQuota($this->kepala, $this->dormitory->id), 'Kepala asrama (head_id) boleh.');
        $this->assertTrue($access->canManageQuota($this->wakil, $this->dormitory->id), 'Wakil Kepala Asrama boleh.');
        $this->assertFalse($access->canManageQuota($this->staf, $this->dormitory->id), 'Staf asrama biasa tidak boleh.');
    }

    public function test_hitungan_kuota_kunjungan_per_bulan_benar(): void
    {
        // Dua kunjungan disetujui pada tanggal berbeda dalam bulan yang sama.
        $this->insertVisitEvent(now()->startOfMonth()->addHours(9));
        $this->insertVisitEvent(now()->startOfMonth()->addDays(5)->addHours(10));

        Cache::flush();

        $count = app(\App\Domain\Services\BoardingRulesEngine::class)
            ->countUsageForCurrentPeriod($this->student->id, 'visit', $this->dormitory->id, 'monthly');

        $this->assertSame(2, $count, 'Kuota bulanan harus menghitung seluruh bulan, bukan hanya tanggal 1.');
    }

    public function test_inspect_kuota_kunjungan_mengembalikan_sisa(): void
    {
        $this->insertVisitEvent(now()->startOfMonth()->addHours(9));
        Cache::flush();

        $info = app(VisitWorkflowService::class)->inspectQuotaForVisit($this->student->id, $this->dormitory->id);

        $this->assertSame('quota', $info['strategy']);
        $this->assertSame(2, $info['quota']);
        $this->assertSame(1, $info['used']);
        $this->assertSame(1, $info['remaining']);
        $this->assertSame('bulan ini', $info['period_label_id']);
    }

    public function test_satu_kunjungan_dihitung_sekali_setelah_check_in(): void
    {
        $visitId = $this->insertVisitEvent(now()->startOfMonth()->addHours(9));

        // Event check-in untuk kunjungan yang sama tidak boleh menambah kuota.
        DB::table('boarding_timeline_events')->insert([
            'id' => (string) Str::uuid(),
            'event_type' => BoardingTimelineEvent::TYPE_VISIT_CHECK_IN,
            'student_id' => $this->student->id,
            'dormitory_id' => $this->dormitory->id,
            'event_at' => now()->startOfMonth()->addHours(10),
            'subject_refs' => json_encode(['visit_id' => $visitId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::flush();

        $count = app(\App\Domain\Services\BoardingRulesEngine::class)
            ->countUsageForCurrentPeriod($this->student->id, 'visit', $this->dormitory->id, 'monthly');
        $info = app(VisitWorkflowService::class)->inspectQuotaForVisit($this->student->id, $this->dormitory->id);

        $this->assertSame(1, $count, 'Approve + check-in untuk satu kunjungan harus dihitung satu kali.');
        $this->assertSame(1, $info['used']);
        $this->assertSame(1, $info['remaining']);
    }

    public function test_pengajuan_kunjungan_ditolak_saat_kuota_habis(): void
    {
        $this->insertVisitEvent(now()->startOfMonth()->addHours(9));
        $this->insertVisitEvent(now()->startOfMonth()->addDays(1)->addHours(9));
        Cache::flush();

        $this->expectException(\App\Domain\Exceptions\QuotaExceededException::class);

        app(VisitWorkflowService::class)->submit([
            'student_id' => $this->student->id,
            'visitor_name' => 'Wali Ketiga',
            'visitor_relationship' => 'wali',
            'purpose' => 'menjenguk',
            'expected_arrival_datetime' => now()->addHour(),
        ], $this->dormitory->id);
    }

    public function test_export_excel_perizinan_terstruktur(): void
    {
        $permit = new DormitoryPermit([
            'permit_type' => 'pulang',
            'category' => 'keluarga',
            'destination' => 'Rumah orang tua',
            'purpose' => 'Acara keluarga',
            'departure_datetime' => now(),
            'expected_return_datetime' => now()->addDay(),
            'companion_name' => 'Ayah',
            'pickup_mode' => 'dijemput',
            'scan_token' => 'TOKEN-1',
            'scanned_at' => now(),
            'status' => 'approved',
        ]);
        $permit->id = (string) Str::uuid();
        $permit->setRelation('student', new Student(['name' => 'Santri Export', 'nisn' => '1231231234']));
        $permit->setRelation('room', null);

        $export = new DormitoryPermitsExport(collect([$permit]), $this->dormitory, (int) now()->month, (int) now()->year);

        $this->assertCount(18, $export->headings());
        $this->assertSame('No', $export->headings()[0]);
        $this->assertSame('Perizinan', $export->title());

        $row = $export->map($permit);
        $this->assertCount(18, $row);
        $this->assertSame(1, $row[0]);
        $this->assertSame('Santri Export', $row[3]);
        $this->assertSame('Ya', $row[14], 'Verifikasi QR terbaca.');

        $styles = $export->styles(new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet);
        $this->assertTrue($styles[1]['font']['bold']);
    }

    // ─────────────────────────────────────────────────────────────

    private function insertVisitEvent(\Illuminate\Support\Carbon $at): string
    {
        // Satu kunjungan menghasilkan satu baris domain + satu event timeline.
        $visitId = (string) Str::uuid();

        DB::table('dormitory_visit_logs')->insert([
            'id' => $visitId,
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->student->id,
            'visitor_name' => 'Wali Santri',
            'visitor_relationship' => 'wali',
            'purpose' => 'menjenguk',
            'expected_arrival_datetime' => $at,
            'status' => 'approved',
            'approved_at' => $at,
            'approved_by' => $this->kepala->id,
            'created_by' => $this->kepala->id,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        DB::table('boarding_timeline_events')->insert([
            'id' => (string) Str::uuid(),
            'event_type' => BoardingTimelineEvent::TYPE_VISIT_APPROVED,
            'student_id' => $this->student->id,
            'dormitory_id' => $this->dormitory->id,
            'event_at' => $at,
            'subject_refs' => json_encode(['visit_id' => $visitId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $visitId;
    }

    private function makeUser(string $name, string $email, string $jabatan, ?string $headOf = null): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => 'test|'.$user->id,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.'scope'),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => $jabatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user;
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Asrama',
            'code' => 'UU-ASR',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '78787878',
            'name' => 'Sekolah Uji Asrama',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-01',
            'name' => 'Asrama Putra Uji',
            'gender' => 'putra',
        ]);

        $this->kepala = $this->makeUser('Kepala Asrama Uji', 'kepala.asrama@test.local', 'Kepala Asrama');
        $this->wakil = $this->makeUser('Wakil Kepala Asrama Uji', 'wakil.asrama@test.local', 'Wakil Kepala Asrama');
        $this->staf = $this->makeUser('Staf Asrama Uji', 'staf.asrama@test.local', 'Staf Asrama');

        $this->dormitory->forceFill(['head_id' => $this->kepala->id])->save();

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '6667778889',
            'name' => 'Santri Kuota',
            'gender' => 'L',
        ]);

        // Kebijakan kunjungan: kuota 2 per bulan.
        $policy = BoardingPolicy::create([
            'id' => (string) Str::uuid(),
            'code' => 'VISIT-DEFAULT',
            'name' => 'Kebijakan Kunjungan Default',
            'visit_strategy' => 'quota',
            'visit_quota' => 2,
            'visit_quota_period' => 'monthly',
            'is_active' => true,
        ]);

        DB::table('dormitory_policy_assignments')->insert([
            'id' => (string) Str::uuid(),
            'boarding_policy_id' => $policy->id,
            'target_id' => $this->dormitory->id,
            'policy_assignment_type' => 'dormitory',
            'priority' => 1,
            'effective_from' => now()->subMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
