<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryNotificationSetting;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use App\Services\Dormitory\ReturnReminderService;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use App\Services\NotificationUniversalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Pengingat otomatis santri yang belum kembali ke asrama:
 * interval, batas pengingat, kanal notifikasi, eskalasi, dan konfigurasinya.
 */
class DormitoryReturnReminderTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private Student $student;

    private StudentMahrom $guardian;

    private AcademicYear $academicYear;

    private User $admin;

    private Mockery\MockInterface $whatsapp;

    private Mockery\MockInterface $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Reminder', 'code' => 'UU-REMIND',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77889900',
            'name' => 'Sekolah Reminder',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-REMIND',
            'name' => 'Asrama Reminder',
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

        $room = DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'R1',
            'name' => 'Kamar 1',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->room = $room;

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '8888000001',
            'name' => 'Santri Reminder',
            'gender' => 'L',
            'status' => 'active',
            'boarding_status' => 'boarding',
        ]);

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'check_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->guardian = StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Santri',
            'relationship' => 'ayah',
            'phone' => '081200000001',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        $this->whatsapp = Mockery::mock(GuardianWhatsAppNotifier::class);
        $this->notifications = Mockery::mock(NotificationUniversalService::class);

        $this->app->instance(GuardianWhatsAppNotifier::class, $this->whatsapp);
        $this->app->instance(NotificationUniversalService::class, $this->notifications);
    }

    private function makePermit(CarbonImmutable $expectedReturn, array $overrides = []): DormitoryPermit
    {
        return DormitoryPermit::create(array_merge([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'dormitory_id' => $this->dormitory->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah wali',
            'departure_datetime' => $expectedReturn->subDays(2),
            'expected_return_datetime' => $expectedReturn,
            'status' => DormitoryPermit::STATUS_APPROVED,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    private function makeSettings(array $overrides = []): DormitoryNotificationSetting
    {
        return DormitoryNotificationSetting::create(array_merge([
            'dormitory_id' => $this->dormitory->id,
            'is_enabled' => true,
            'h1_reminder_enabled' => true,
            'h1_reminder_time' => '16:00',
            'interval_minutes' => 30,
            'max_reminders' => 3,
            'escalate_after_minutes' => 180,
            'notify_staff' => true,
            'channels' => ['whatsapp', 'push', 'in_app'],
        ], $overrides));
    }

    private function service(): ReturnReminderService
    {
        return $this->app->make(ReturnReminderService::class);
    }

    private function expectNoMessages(): void
    {
        $this->whatsapp->shouldNotReceive('notifyOverdue');
        $this->notifications->shouldNotReceive('notifyGuardiansForStudents');
    }

    public function test_pengingat_terkirim_setelah_batas_kembali_terlewati(): void
    {
        $this->makeSettings();
        $now = CarbonImmutable::parse('2026-11-02 10:00:00');
        $permit = $this->makePermit($now->subMinutes(5));

        $this->whatsapp->shouldReceive('notifyOverdue')->once()->with(Mockery::on(fn ($p) => $p->id === $permit->id))->andReturn(true);
        $this->notifications->shouldReceive('notifyGuardiansForStudents')->once()->andReturn(1);

        $result = $this->service()->process($now);

        $this->assertSame(1, $result['reminders']);
        $permit->refresh();
        $this->assertSame(1, $permit->overdue_notified_count);
        $this->assertNotNull($permit->overdue_notified_at);
    }

    public function test_pengingat_menghormati_interval_per_menit(): void
    {
        $this->makeSettings(['interval_minutes' => 30]);
        $now = CarbonImmutable::parse('2026-11-02 10:00:00');
        $permit = $this->makePermit($now->subHours(2), [
            'overdue_notified_count' => 1,
            'overdue_notified_at' => $now->subMinutes(10),
        ]);

        // Sebelum interval terlewati → tidak ada kiriman (mock akan error bila terkirim).
        $this->assertSame(0, $this->service()->process($now)['reminders']);

        // Lewat interval → kirim lagi.
        $permit->forceFill(['overdue_notified_at' => $now->subMinutes(31)])->save();

        $this->whatsapp->shouldReceive('notifyOverdue')->once()->andReturn(true);
        $this->notifications->shouldReceive('notifyGuardiansForStudents')->once()->andReturn(1);

        $this->assertSame(1, $this->service()->process($now)['reminders']);
        $this->assertSame(2, $permit->fresh()->overdue_notified_count);
    }

    public function test_pengingat_berhenti_setelah_maksimal(): void
    {
        $this->makeSettings(['max_reminders' => 2]);
        $now = CarbonImmutable::parse('2026-11-02 10:00:00');
        $this->makePermit($now->subHours(2), [
            'overdue_notified_count' => 2,
            'overdue_notified_at' => $now->subHour(),
        ]);

        $this->expectNoMessages();
        $this->assertSame(0, $this->service()->process($now)['reminders']);
    }

    public function test_pengingat_tidak_aktif_tidak_mengirim(): void
    {
        $this->makeSettings(['is_enabled' => false]);
        $now = CarbonImmutable::parse('2026-11-02 10:00:00');
        $this->makePermit($now->subHours(2));

        $this->expectNoMessages();
        $result = $this->service()->process($now);

        $this->assertSame(0, $result['reminders']);
        $this->assertSame(0, $result['escalations']);
    }

    public function test_kanal_notifikasi_dihormati(): void
    {
        $this->makeSettings(['channels' => ['push']]);
        $now = CarbonImmutable::parse('2026-11-02 10:00:00');
        $this->makePermit($now->subMinutes(5));

        $this->whatsapp->shouldNotReceive('notifyOverdue');
        $this->notifications->shouldReceive('notifyGuardiansForStudents')
            ->once()
            ->with(Mockery::any(), Mockery::on(fn (array $data) => $data['send_push'] === true
                && $data['send_email'] === false
                && $data['send_in_app'] === false
                && $data['send_whatsapp'] === false))
            ->andReturn(1);

        $this->assertSame(1, $this->service()->process($now)['reminders']);
    }

    public function test_eskalasi_ke_petugas_setelah_ambang_waktu(): void
    {
        $this->makeSettings(['max_reminders' => 1, 'escalate_after_minutes' => 180]);
        $now = CarbonImmutable::parse('2026-11-02 10:00:00');
        $permit = $this->makePermit($now->subHours(4), [
            'overdue_notified_count' => 1,
            'overdue_notified_at' => $now->subHours(3),
        ]);

        $this->notifications->shouldNotReceive('notifyGuardiansForStudents');
        $this->whatsapp->shouldNotReceive('notifyOverdue');
        $this->notifications->shouldReceive('notifyDormitoryStaff')->once()->andReturn(2);

        $result = $this->service()->process($now);

        $this->assertSame(1, $result['escalations']);
        $permit->refresh();
        $this->assertSame(DormitoryPermit::STATUS_OVERDUE, $permit->status);
        $this->assertNotNull($permit->escalation_triggered_at);
        $this->assertTrue(
            BoardingTimelineEvent::where('student_id', $this->student->id)
                ->where('event_type', BoardingTimelineEvent::TYPE_LEAVE_OVERDUE)
                ->exists(),
            'Eskalasi tercatat di timeline asrama.'
        );
    }

    public function test_pengingat_h1_hanya_sekali_pada_jam_yang_ditentukan(): void
    {
        $this->makeSettings(['h1_reminder_time' => '16:00', 'channels' => ['whatsapp']]);
        $now = CarbonImmutable::parse('2026-11-02 16:00:00');
        $permit = $this->makePermit($now->addDay()->setTime(10, 0));

        $this->whatsapp->shouldReceive('notifyReturnReminder')->once()->andReturn(true);
        $this->notifications->shouldReceive('notifyGuardiansForStudents')->once()->andReturn(1);

        $this->assertSame(1, $this->service()->processHeadStartReminders($now));
        $this->assertNotNull($permit->fresh()->return_reminder_sent_at);

        // Proses ulang pada menit yang sama tidak mengirim ganda.
        $this->assertSame(0, $this->service()->processHeadStartReminders($now));
    }

    public function test_pengingat_h1_tidak_dikirim_di_luar_jam_pengaturan(): void
    {
        $this->makeSettings(['h1_reminder_time' => '16:00']);
        $now = CarbonImmutable::parse('2026-11-02 15:30:00');
        $this->makePermit($now->addDay()->setTime(10, 0));

        $this->expectNoMessages();

        $this->assertSame(0, $this->service()->processHeadStartReminders($now));
    }

    public function test_halaman_pengaturan_dapat_disimpan(): void
    {
        $url = route('user.asrama.notifications.edit', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->dormitory->id,
        ]);

        $this->actingAs($this->admin)
            ->get($url)
            ->assertOk()
            ->assertSee('Pengaturan Notifikasi Kembali');

        $this->actingAs($this->admin)
            ->put($url, [
                'is_enabled' => '1',
                'h1_reminder_enabled' => '0',
                'h1_reminder_time' => '17:30',
                'interval_minutes' => 15,
                'max_reminders' => 4,
                'escalate_after_minutes' => 240,
                'notify_staff' => '1',
                'channels' => ['whatsapp', 'email'],
                'message_template' => 'Halo {wali}, {santri} belum kembali.',
            ])
            ->assertRedirect();

        $setting = DormitoryNotificationSetting::where('dormitory_id', $this->dormitory->id)->firstOrFail();

        $this->assertTrue($setting->is_enabled);
        $this->assertFalse($setting->h1_reminder_enabled);
        $this->assertSame('17:30', $setting->h1_reminder_time);
        $this->assertSame(15, $setting->interval_minutes);
        $this->assertSame(4, $setting->max_reminders);
        $this->assertSame(['whatsapp', 'email'], $setting->channels);
    }

    public function test_form_kunjungan_wajib_isi_waktu_kembali(): void
    {
        $url = route('user.asrama.visits.store', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->dormitory->id,
        ]);

        $this->actingAs($this->admin)
            ->post($url, [
                'student_id' => $this->student->id,
                'visitor_name' => 'Pengunjung Uji',
                'visitor_id_number' => '3201000000000001',
                'visitor_gender' => 'putra',
                'visitor_relationship' => 'wali',
                'purpose' => 'menjenguk',
                'expected_arrival_datetime' => '2026-11-03 09:00:00',
                'visitor_count' => 1,
                'rules_acknowledged' => '1',
            ])
            ->assertSessionHasErrors('departure_datetime');
    }
}
