<?php

namespace Tests\Feature;

use App\Models\DormitoryPermit;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Notifikasi WA wali santri (alternatif gateway: fonnte/wablas/generic/log):
 * dijemput, kembali ke asrama, dan sakit.
 */
class WhatsAppGuardianNotificationTest extends TestCase
{
    use RefreshDatabase;

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

        Http::preventStrayRequests();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji WA',
            'code' => 'UU-WA',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '90909090',
            'name' => 'Sekolah Uji WA',
        ]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'nisn' => '5554443332',
            'name' => 'Santri Notif',
            'gender' => 'L',
        ]);

        // Mahrom utama dengan nomor HP format lokal.
        DB::table('student_mahroms')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Ayah Santri',
            'relationship' => 'ayah',
            'phone' => '0812-3456-7890',
            'is_active' => true,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config()->set('whatsapp.enabled', true);
        config()->set('whatsapp.provider', 'fonnte');
        config()->set('whatsapp.token', 'test-token');
        config()->set('whatsapp.url', 'https://api.fonnte.com/send');
    }

    public function test_notifikasi_dijemput_dan_kembali_terkirim_ke_wali(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $permit = $this->makePermit([
            'pickup_scanned_at' => now(),
            'actual_return_datetime' => now(),
            'destination' => 'Rumah orang tua',
        ]);

        $notifier = app(GuardianWhatsAppNotifier::class);

        $this->assertTrue($notifier->notifyPickedUp($permit));
        $this->assertTrue($notifier->notifyReturned($permit));

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request['target'] === '6281234567890'
                && str_contains($request['message'], 'DIJEMPUT');
        });

        Http::assertSent(function ($request) {
            return str_contains($request['message'], 'KEMBALI KE ASRAMA');
        });
    }

    public function test_notifikasi_sakit_terkirim_ke_wali(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $sent = app(GuardianWhatsAppNotifier::class)
            ->notifySick($this->student, 'Demam tinggi', 'uks');

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            return $request['target'] === '6281234567890'
                && str_contains($request['message'], 'SAKIT')
                && str_contains($request['message'], 'Demam tinggi');
        });
    }

    public function test_driver_log_gratis_tidak_memanggil_http(): void
    {
        config()->set('whatsapp.provider', 'log');
        Http::fake();

        $sent = app(GuardianWhatsAppNotifier::class)->notifySick($this->student);

        $this->assertTrue($sent, 'Driver log mengembalikan sukses (dicatat untuk kirim manual).');
        Http::assertNothingSent();
    }

    public function test_normalisasi_nomor_indonesia(): void
    {
        $gateway = app(WhatsAppGatewayService::class);

        $this->assertSame('6281234567890', $gateway->normalizePhone('0812-3456-7890'));
        $this->assertSame('6281234567890', $gateway->normalizePhone('81234567890'));
        $this->assertSame('6281234567890', $gateway->normalizePhone('+62 812 3456 7890'));
        $this->assertNull($gateway->normalizePhone(''));
    }


    public function test_notifikasi_penghargaan_terkirim_dan_bisa_dinonaktifkan(): void
    {
        config()->set('whatsapp.delivery_mode', 'central');
        config()->set('whatsapp.central_number', '0811-2222-3333');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $reward = new \App\Models\DormitoryReward([
            'title' => 'Juara Tahfidz',
            'category' => \App\Models\DormitoryReward::CAT_TAHFIDZ,
            'level' => \App\Models\DormitoryReward::LEVEL_UGGALAN,
            'points' => 25,
            'description' => 'Hafalan 5 juz',
        ]);
        $reward->id = (string) Str::uuid();
        $reward->setRelation('student', $this->student);

        $this->assertTrue(app(GuardianWhatsAppNotifier::class)->notifyReward($reward));

        Http::assertSent(function ($request) {
            return $request['target'] === '6281122223333'
                && str_contains($request['message'], 'Juara Tahfidz')
                && str_contains($request['message'], 'Poin: +25');
        });

        // Toggle UI wa_event_achievement harus benar-benar mematikan event.
        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'wa_event_achievement'],
            ['value' => '0', 'group' => 'notification', 'type' => 'boolean']
        );
        Cache::flush();

        $this->assertFalse(app(GuardianWhatsAppNotifier::class)->notifyReward($reward));
    }

    public function test_toggle_wa_event_picked_up_mematikan_notifikasi(): void
    {
        config()->set('whatsapp.delivery_mode', 'central');
        config()->set('whatsapp.central_number', '0811-2222-3333');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'wa_event_picked_up'],
            ['value' => '0', 'group' => 'notification', 'type' => 'boolean']
        );
        Cache::flush();

        $permit = $this->makePermit(['pickup_scanned_at' => now()]);

        $this->assertFalse(app(GuardianWhatsAppNotifier::class)->notifyPickedUp($permit));
        Http::assertNothingSent();
    }

    public function test_mode_terpusat_mengirim_ke_nomor_admin_dan_tercatat(): void
    {
        config()->set('whatsapp.delivery_mode', 'central');
        config()->set('whatsapp.central_number', '0811-2222-3333');

        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $permit = $this->makePermit([
            'pickup_scanned_at' => now(),
            'destination' => 'Rumah',
        ]);

        $this->assertTrue(app(GuardianWhatsAppNotifier::class)->notifyPickedUp($permit));

        // Terpusat ke nomor admin ALIM (ternormalisasi), menyertakan identitas wali.
        Http::assertSent(function ($request) {
            return $request['target'] === '6281122223333'
                && str_contains($request['message'], 'Untuk wali')
                && str_contains($request['message'], 'Santri Notif');
        });

        // Riwayat pengiriman tersimpan.
        $this->assertTrue(
            DB::table('wa_message_logs')
                ->where('phone', '6281122223333')
                ->where('event', 'permit_picked_up')
                ->exists(),
            'Log pengiriman WA tersimpan.'
        );
    }

    public function test_template_pesan_dapat_ditimpa_via_settings(): void
    {
        config()->set('whatsapp.delivery_mode', 'central');
        config()->set('whatsapp.central_number', '081122223333');

        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'wa.template.picked_up'],
            ['value' => 'Kustom: {santri} dijemput pukul {waktu}.', 'group' => 'whatsapp', 'type' => 'string']
        );
        Cache::flush();

        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $permit = $this->makePermit(['pickup_scanned_at' => now()]);
        $this->assertTrue(app(GuardianWhatsAppNotifier::class)->notifyPickedUp($permit));

        Http::assertSent(function ($request) {
            return str_contains($request['message'], 'Kustom:')
                && str_contains($request['message'], 'Santri Notif dijemput');
        });
    }

    public function test_notifikasi_libur_terpusat_dan_legenda_default_kaldik(): void
    {
        config()->set('whatsapp.delivery_mode', 'central');
        config()->set('whatsapp.central_number', '081122223333');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        // Legenda default: libur = merah; dapat ditimpa settings.
        $this->assertSame('#EF4444', kaldik_default_color('libur'));
        $this->assertSame('#EF4444', kaldik_legend()['libur']['color']);

        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'kaldik_legend_libur'],
            ['value' => '#DC2626', 'group' => 'kaldik', 'type' => 'string']
        );
        Cache::flush();
        $this->assertSame('#DC2626', kaldik_default_color('libur'));

        // Pemberitahuan libur terkirim ke nomor admin ALIM.
        $kaldik = new \App\Models\Kaldik;
        $kaldik->id = (string) Str::uuid();
        $kaldik->name = 'Libur Semester';
        $kaldik->type = \App\Models\Kaldik::TYPE_LIBUR;
        $kaldik->start_date = now();

        $this->assertTrue(app(GuardianWhatsAppNotifier::class)->notifyHoliday($kaldik));
        Http::assertSent(function ($request) {
            return $request['target'] === '6281122223333'
                && str_contains($request['message'], 'Libur Semester');
        });
    }


    public function test_notifikasi_kunjungan_dan_pengingat_kembali(): void
    {
        config()->set('whatsapp.delivery_mode', 'central');
        config()->set('whatsapp.central_number', '081122223333');
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        // Kunjungan disetujui → wali diberi tahu.
        $visit = new \App\Models\DormitoryVisitLog([
            'visitor_name' => 'Ayah Santri',
            'expected_arrival_datetime' => now()->addDay(),
        ]);
        $visit->id = (string) Str::uuid();
        $visit->setRelation('student', $this->student);

        $this->assertTrue(app(GuardianWhatsAppNotifier::class)->notifyVisit($visit));

        Http::assertSent(function ($request) {
            return $request['target'] === '6281122223333'
                && str_contains($request['message'], 'Kunjungan');
        });

        // Pengingat H-1 kembali ke asrama.
        $permit = $this->makePermit(['expected_return_datetime' => now()->addDay()]);
        $this->assertTrue(app(GuardianWhatsAppNotifier::class)->notifyReturnReminder($permit));

        Http::assertSent(function ($request) {
            return str_contains($request['message'], 'kembali ke asrama');
        });
    }

    private function makePermit(array $overrides = []): DormitoryPermit
    {
        $permit = new DormitoryPermit;
        $permit->id = (string) Str::uuid();
        $permit->forceFill(array_merge([
            'permit_type' => 'pulang',
            'departure_datetime' => now(),
            'expected_return_datetime' => now()->addDay(),
            'companion_name' => 'Ayah',
            'companion_relation' => 'Ayah',
        ], $overrides));
        $permit->setRelation('student', $this->student);

        return $permit;
    }
}
