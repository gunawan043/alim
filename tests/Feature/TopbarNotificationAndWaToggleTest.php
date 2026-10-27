<?php

namespace Tests\Feature;

use App\Listeners\SendLoginNotification;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\NotificationUniversalService;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Notifikasi topbar: per satuan kerja, login, dan toggle WA per jenis.
 */
class TopbarNotificationAndWaToggleTest extends TestCase
{
    use RefreshDatabase;

    private string $workUnitA;

    private string $workUnitB;

    private User $userA;

    private User $userB;

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

        $this->workUnitA = (string) Str::uuid();
        $this->workUnitB = (string) Str::uuid();

        foreach ([['A', $this->workUnitA], ['B', $this->workUnitB]] as [$code, $id]) {
            DB::table('work_units')->insert([
                'id' => $id,
                'name' => 'Unit '.$code,
                'code' => 'WU-'.$code,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->userA = $this->makeUser('Pengguna A', 'user.a@notif.test', $this->workUnitA, $role);
        $this->userB = $this->makeUser('Pengguna B', 'user.b@notif.test', $this->workUnitB, $role);
    }

    public function test_notifikasi_per_satuan_kerja_hanya_ke_satuan_terkait(): void
    {
        $service = app(NotificationUniversalService::class);

        $service->sendToWorkUnit($this->workUnitA, [
            'module' => 'kaldik',
            'type' => 'info',
            'action' => 'kaldik_created',
            'title' => 'Kaldik Unit A',
            'message' => 'Agenda khusus Unit A.',
        ]);

        // Hanya pengguna Unit A yang menerima.
        $this->assertSame(
            1,
            DB::table('notifications_universal')->where('user_id', $this->userA->id)->where('work_unit_id', $this->workUnitA)->count()
        );
        $this->assertSame(
            0,
            DB::table('notifications_universal')->where('user_id', $this->userB->id)->count(),
            'Notifikasi tidak bocor ke satuan kerja lain.'
        );

        // Scope unread: Unit A melihat miliknya; Unit B tidak.
        $this->assertGreaterThanOrEqual(1, $service->getUnreadCount($this->userA->id, $this->workUnitA));
        $this->assertSame(0, $service->getUnreadCount($this->userB->id, $this->workUnitB));

        // Notifikasi global (tanpa work unit) terbaca semua.
        $service->send($this->userB->id, ['module' => 'system', 'type' => 'info', 'title' => 'Global', 'message' => 'Info global.']);
        $this->assertGreaterThanOrEqual(1, $service->getUnreadCount($this->userB->id, $this->workUnitB));
    }

    public function test_login_membuat_notifikasi_perangkat(): void
    {
        app(SendLoginNotification::class)->handle(new Login('web', $this->userA, false));

        $row = DB::table('notifications_universal')
            ->where('user_id', $this->userA->id)
            ->where('module', 'auth')
            ->first();

        $this->assertNotNull($row, 'Notifikasi login dibuat.');
        $this->assertStringContainsString('Login baru', (string) $row->title);
        $this->assertStringContainsString('IP', (string) $row->message);
    }

    public function test_toggle_wa_per_jenis_notifikasi(): void
    {
        // Nonaktifkan notifikasi sakit lewat settings.
        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'wa_event_sick'],
            ['value' => '0', 'group' => 'whatsapp', 'type' => 'boolean']
        );
        \Illuminate\Support\Facades\Cache::flush();

        $student = new \App\Models\Student(['name' => 'Santri Uji', 'school_id' => (string) Str::uuid()]);
        $student->id = (string) Str::uuid();

        $notifier = app(GuardianWhatsAppNotifier::class);
        $this->assertFalse($notifier->notifySick($student), 'Toggle off → tidak dikirim.');

        // Nyalakan kembali → guard lolos (walau tanpa nomor, return false karena tak ada target).
        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'wa_event_sick'],
            ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']
        );
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertFalse($notifier->notifySick($student), 'Aktif tetapi tanpa penerima tetap aman.');
    }


    public function test_resolusi_satuan_kerja_via_kepegawaian_dan_global_fallback(): void
    {
        $service = app(NotificationUniversalService::class);

        // User C: TIDAK punya baris gtk_work_unit, hanya kepegawaian (sekolah → work unit A).
        $school = School::create([
            'work_unit_id' => $this->workUnitA,
            'npsn' => '77889900',
            'name' => 'Sekolah Unit A',
        ]);
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $userC = User::create([
            'name' => 'Guru Unit A', 'email' => 'guru.a@notif.test',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $userC->assignRole($role);
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $userC->id, 'school_id' => $school->id,
            'status_kepegawaian' => 'GTY', 'jenis_gtk' => 'Pendidik / Guru', 'jabatan' => 'Guru Mapel',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $ids = $service->userIdsForWorkUnit($this->workUnitA);
        $this->assertContains($userC->id, $ids, 'Resolusi via kepegawaian (sekolah → work unit).');

        // notifyGtk tanpa satuan kerja → GLOBAL ke semua pengguna aktif.
        $count = $service->notifyGtk(null, [
            'module' => 'system', 'type' => 'info',
            'title' => 'Pengumuman Global', 'message' => 'Berlaku semua satuan kerja.',
        ]);

        $this->assertGreaterThanOrEqual(3, $count);
        $this->assertSame(
            1,
            DB::table('notifications_universal')->where('user_id', $this->userB->id)
                ->where('title', 'Pengumuman Global')->whereNull('work_unit_id')->count()
        );
    }


    public function test_endpoint_topbar_mengembalikan_json_notifikasi_milik_pengguna(): void
    {
        $service = app(NotificationUniversalService::class);
        $service->send($this->userA->id, ['module' => 'system', 'type' => 'info', 'title' => 'Milik A', 'message' => 'Pesan A']);
        $service->send($this->userB->id, ['module' => 'system', 'type' => 'info', 'title' => 'Milik B', 'message' => 'Pesan B']);

        $this->actingAs($this->userA)
            ->getJson("/{$this->userA->id}/notifications")
            ->assertOk()
            ->assertJsonPath('notifications.0.title', 'Milik A')
            ->assertJsonPath('unread_count', 1);
    }


    public function test_halaman_lihat_semua_notifikasi_render(): void
    {
        app(NotificationUniversalService::class)->send($this->userA->id, [
            'module' => 'system', 'type' => 'info', 'title' => 'Halaman Uji', 'message' => 'Isi uji halaman.',
        ]);

        $this->actingAs($this->userA)
            ->get("/{$this->userA->id}/notifications/page")
            ->assertOk()
            ->assertSee('Halaman Uji');
    }


    public function test_endpoint_unread_count_mengembalikan_angka(): void
    {
        app(NotificationUniversalService::class)->send($this->userA->id, [
            'module' => 'system', 'type' => 'info', 'title' => 'Hitung', 'message' => 'Cek hitung.',
        ]);

        $this->actingAs($this->userA)
            ->getJson("/{$this->userA->id}/notifications/unread-count")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1);
    }


    public function test_endpoint_pencarian_http(): void
    {
        $this->actingAs($this->userA)
            ->getJson("/{$this->userA->id}/search?q=santri")
            ->assertOk()
            ->assertJsonStructure(['menus', 'students']);
    }


    public function test_mark_all_read_hanya_akun_sendiri(): void
    {
        $svc = app(NotificationUniversalService::class);
        $svc->send($this->userA->id, ['module' => 'system', 'type' => 'info', 'title' => 'A1', 'message' => 'x']);
        $svc->send($this->userA->id, ['module' => 'system', 'type' => 'info', 'title' => 'A2', 'message' => 'x']);
        $svc->send($this->userB->id, ['module' => 'system', 'type' => 'info', 'title' => 'B1', 'message' => 'x']);

        $this->actingAs($this->userA)
            ->postJson("/{$this->userA->id}/notifications/mark-all-read")
            ->assertOk();

        $this->assertSame(0, DB::table('notifications_universal')->where('user_id', $this->userA->id)->where('is_read', false)->count());
        $this->assertSame(1, DB::table('notifications_universal')->where('user_id', $this->userB->id)->where('is_read', false)->count(), 'Akun lain TIDAK ikut tertandai.');
    }

    public function test_tidak_dapat_menandai_notifikasi_akun_lain(): void
    {
        app(NotificationUniversalService::class)->send($this->userB->id, ['module' => 'system', 'type' => 'info', 'title' => 'B Rahasia', 'message' => 'x']);
        $notifB = DB::table('notifications_universal')->where('user_id', $this->userB->id)->first();

        $this->actingAs($this->userA)
            ->postJson("/{$this->userA->id}/notifications/{$notifB->id}/mark-read")
            ->assertNotFound();

        $this->assertFalse((bool) DB::table('notifications_universal')->where('id', $notifB->id)->value('is_read'));
    }


    public function test_url_notifikasi_absolut_lokal_dinormalkan_ke_path_relatif(): void
    {
        app(NotificationUniversalService::class)->send($this->userA->id, [
            'module' => 'system',
            'type' => 'info',
            'title' => 'URL Lokal',
            'message' => 'Uji normalisasi URL.',
            'action_url' => 'http://127.0.0.1:8000/'.$this->userA->id.'/notifications/page',
        ]);

        $notification = \App\Models\NotificationUniversal::where('user_id', $this->userA->id)
            ->where('title', 'URL Lokal')
            ->firstOrFail();

        // Penyimpanan tetap absolut untuk email/push; tampilan memakai path relatif.
        $this->assertStringStartsWith('http://127.0.0.1:8000/', $notification->getRawOriginal('action_url'));
        $this->assertSame('/'.$this->userA->id.'/notifications/page', $notification->action_url);
    }

    public function test_url_kaldik_lama_memakai_user_penerima(): void
    {
        app(NotificationUniversalService::class)->send($this->userA->id, [
            'module' => 'kaldik',
            'type' => 'info',
            'title' => 'Kaldik Lama',
            'message' => 'Uji URL Kaldik.',
            // Simulasikan data lama: userId pembuat, bukan penerima.
            'action_url' => '/'.$this->userB->id.'/kaldik',
        ]);

        $notification = \App\Models\NotificationUniversal::where('user_id', $this->userA->id)
            ->where('title', 'Kaldik Lama')
            ->firstOrFail();

        $this->assertSame('/'.$this->userA->id.'/kaldik', $notification->action_url);
    }

    public function test_notifikasi_tanpa_url_mendapat_fallback_halaman_terkait(): void
    {
        $service = app(NotificationUniversalService::class);
        $service->send($this->userA->id, [
            'module' => 'system',
            'type' => 'info',
            'title' => 'Login Tanpa URL',
            'message' => 'Uji fallback.',
            'action' => 'login',
        ]);

        $notification = \App\Models\NotificationUniversal::where('user_id', $this->userA->id)
            ->where('title', 'Login Tanpa URL')
            ->firstOrFail();

        $this->assertSame('/'.$this->userA->id.'/profile/security', $notification->action_url);

        $this->actingAs($this->userA)
            ->getJson('/'.$this->userA->id.'/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.0.action_url', '/'.$this->userA->id.'/profile/security');
    }

    public function test_action_url_callable_menggunakan_user_penerima(): void
    {
        $service = app(NotificationUniversalService::class);
        $service->sendToMany([$this->userA->id, $this->userB->id], [
            'module' => 'kaldik',
            'type' => 'info',
            'title' => 'Kaldik per Penerima',
            'message' => 'Uji URL per penerima.',
            'action_url' => fn (string $recipientId) => '/'.$recipientId.'/kaldik',
        ]);

        $notifA = \App\Models\NotificationUniversal::where('user_id', $this->userA->id)
            ->where('title', 'Kaldik per Penerima')
            ->firstOrFail();
        $notifB = \App\Models\NotificationUniversal::where('user_id', $this->userB->id)
            ->where('title', 'Kaldik per Penerima')
            ->firstOrFail();

        $this->assertSame('/'.$this->userA->id.'/kaldik', $notifA->action_url);
        $this->assertSame('/'.$this->userB->id.'/kaldik', $notifB->action_url);
    }

    public function test_topbar_menampilkan_tautan_halaman_notifikasi_bukan_endpoint_json(): void
    {
        $this->actingAs($this->userA)
            ->get('/'.$this->userA->id.'/notifications/page')
            ->assertOk()
            ->assertSee(route('user.notifications.page', ['userId' => $this->userA->id]), false);
    }

    public function test_jadwal_dormitori_terdaftar(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();

        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->command ?? '')
            ->implode("\n");

        $this->assertStringContainsString('dormitory:process-overdue', $events);
        $this->assertStringContainsString('dormitory:remind-returns', $events);
    }

    private function makeUser(string $name, string $email, string $workUnitId, Role $role): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $workUnitId,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }
}
