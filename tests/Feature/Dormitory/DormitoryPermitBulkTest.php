<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\AsramaConfig;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Izin pulang: kembali otomatis 24 jam, pengaturan tanggal kembali massal,
 * dan pembuatan izin kepulangan masal (satu tanggal untuk banyak santri).
 */
class DormitoryPermitBulkTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private AcademicYear $academicYear;

    private User $admin;

    /** @var array<int, Student> */
    private array $students = [];

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Bulk', 'code' => 'UU-BULK',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '55667788',
            'name' => 'Sekolah Bulk',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-BULK',
            'name' => 'Asrama Bulk',
            'gender' => 'putra',
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'code' => 'W1',
            'name' => 'Sayap 1',
            'gender' => 'putra',
        ]);

        $this->room = DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'R1',
            'name' => 'Kamar 1',
            'gender' => 'putra',
            'capacity' => 20,
            'is_active' => true,
        ]);

        foreach (['Santri Bulk A', 'Santri Bulk B', 'Santri Bulk C'] as $i => $name) {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'school_id' => $this->school->id,
                'nisn' => '999900000'.($i + 1),
                'name' => $name,
                'gender' => 'L',
                'status' => 'active',
                'boarding_status' => 'boarding',
            ]);

            DormitoryResident::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $this->dormitory->id,
                'student_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'room_id' => $this->room->id,
                'bed_number' => $i + 1,
                'check_in_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            $this->students[] = $student;
        }

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        AsramaConfig::setConsentRequired(false);
    }

    private function makePermit(Student $student, array $overrides = []): DormitoryPermit
    {
        return DormitoryPermit::create(array_merge([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $this->dormitory->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah wali',
            'departure_datetime' => now()->subDay(),
            'expected_return_datetime' => now()->addHours(4),
            'status' => DormitoryPermit::STATUS_APPROVED,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_izin_pulang_otomatis_kembali_24_jam(): void
    {
        $departure = CarbonImmutable::parse('2026-11-03 08:00:00');

        $response = $this->actingAs($this->admin)->post(
            route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'student_id' => $this->students[0]->id,
                'room_id' => $this->room->id,
                'permit_type' => 'pulang',
                'destination' => 'Rumah wali',
                'departure_datetime' => $departure->format('Y-m-d H:i:s'),
                // expected_return_datetime sengaja tidak dikirim
                'guardian_consent_confirmed' => '1',
            ]
        );

        $response->assertRedirect();

        $permit = DormitoryPermit::where('student_id', $this->students[0]->id)->firstOrFail();

        $this->assertSame(
            '2026-11-04 08:00:00',
            $permit->expected_return_datetime->format('Y-m-d H:i:s'),
            'Izin pulang otomatis kembali 24 jam setelah berangkat.'
        );
    }

    public function test_izin_selain_pulang_tetap_wajib_isi_tanggal_kembali(): void
    {
        $this->actingAs($this->admin)->post(
            route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'student_id' => $this->students[0]->id,
                'room_id' => $this->room->id,
                'permit_type' => 'berobat',
                'departure_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
                'guardian_consent_confirmed' => '1',
            ]
        )->assertSessionHasErrors('expected_return_datetime');
    }

    public function test_atur_tanggal_kembali_banyak_izin_sekaligus(): void
    {
        $a = $this->makePermit($this->students[0]);
        $b = $this->makePermit($this->students[1]);
        $c = $this->makePermit($this->students[2], [
            'status' => DormitoryPermit::STATUS_RETURNED,
            'actual_return_datetime' => now(),
        ]);

        $newReturn = now()->addDays(2)->setTime(16, 0);

        $this->actingAs($this->admin)->post(
            route('user.asrama.permits.bulk-return', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'permit_ids' => [$a->id, $b->id, $c->id],
                'expected_return_datetime' => $newReturn->format('Y-m-d H:i:s'),
            ]
        )->assertRedirect();

        $this->assertSame(
            $newReturn->format('Y-m-d H:i:s'),
            $a->fresh()->expected_return_datetime->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $newReturn->format('Y-m-d H:i:s'),
            $b->fresh()->expected_return_datetime->format('Y-m-d H:i:s')
        );
        $this->assertNotSame(
            $newReturn->format('Y-m-d H:i:s'),
            $c->fresh()->expected_return_datetime->format('Y-m-d H:i:s'),
            'Izin yang sudah selesai tidak ikut diubah.'
        );
        $this->assertSame(0, $a->fresh()->overdue_notified_count, 'Penghitung pengingat direset.');
    }

    public function test_atur_tanggal_kembali_menolak_tanggal_sebelum_berangkat(): void
    {
        $permit = $this->makePermit($this->students[0], [
            'departure_datetime' => now()->addDay(),
        ]);

        $this->actingAs($this->admin)->post(
            route('user.asrama.permits.bulk-return', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'permit_ids' => [$permit->id],
                'expected_return_datetime' => now()->subDay()->format('Y-m-d H:i:s'),
            ]
        )->assertRedirect();

        $this->assertNotSame(
            now()->subDay()->format('Y-m-d H:i:s'),
            $permit->fresh()->expected_return_datetime->format('Y-m-d H:i:s'),
            'Tanggal kembali sebelum berangkat tidak diterapkan.'
        );
    }

    public function test_kepulangan_masal_membuat_izin_dengan_satu_tanggal_kembali(): void
    {
        $departure = CarbonImmutable::parse('2026-11-10 07:00:00');
        $return = CarbonImmutable::parse('2026-11-11 07:00:00');

        $response = $this->actingAs($this->admin)->post(
            route('user.asrama.permits.massal.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'student_ids' => [$this->students[0]->id, $this->students[1]->id, $this->students[2]->id],
                'permit_type' => 'pulang',
                'departure_datetime' => $departure->format('Y-m-d H:i:s'),
                'expected_return_datetime' => $return->format('Y-m-d H:i:s'),
                'destination' => 'Rumah wali',
                'purpose' => 'Kepulangan bulanan',
                'guardian_consent_confirmed' => '1',
            ]
        );

        $response->assertRedirect(route('user.asrama.permits.index', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->dormitory->id,
        ]));

        $permits = DormitoryPermit::where('dormitory_id', $this->dormitory->id)->get();

        $this->assertCount(3, $permits);
        $this->assertTrue($permits->every(
            fn ($p) => $p->expected_return_datetime->format('Y-m-d H:i:s') === $return->format('Y-m-d H:i:s')
        ), 'Semua izin masal memakai satu tanggal kembali yang sama.');
    }

    public function test_kepulangan_masal_melewati_santri_yang_sudah_punya_izin_aktif(): void
    {
        $this->makePermit($this->students[0]);

        $this->actingAs($this->admin)->post(
            route('user.asrama.permits.massal.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'student_ids' => [$this->students[0]->id, $this->students[1]->id],
                'permit_type' => 'pulang',
                'departure_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
                'expected_return_datetime' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'guardian_consent_confirmed' => '1',
            ]
        )->assertRedirect();

        $this->assertSame(
            1,
            DormitoryPermit::where('student_id', $this->students[0]->id)->count(),
            'Santri dengan izin aktif tidak dibuatkan izin ganda.'
        );
        $this->assertSame(
            1,
            DormitoryPermit::where('student_id', $this->students[1]->id)->count(),
            'Santri lain tetap dibuatkan izin.'
        );
    }

    public function test_halaman_kepulangan_masal_dan_modal_bulk_tersedia(): void
    {
        $indexUrl = route('user.asrama.permits.index', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->dormitory->id,
        ]);

        $this->actingAs($this->admin)
            ->get($indexUrl)
            ->assertOk()
            ->assertSee('Kepulangan Masal')
            ->assertSee('Atur Tanggal Kembali');

        $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.massal.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Kepulangan Masal')
            ->assertSee('Santri Bulk A');
    }
}
