<?php

namespace Tests\Feature;

use App\Models\HumasAnnouncement;
use App\Models\HumasAnnouncementImage;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Dashboard\DashboardCalendarService;
use App\Services\Dashboard\DashboardContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardJabatanTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $roleName): User
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Dashboard '.$roleName, 'code' => 'UD'.substr(md5($roleName), 0, 4),
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '7'.random_int(1000000, 9999999),
            'name' => 'Sekolah '.$roleName,
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Uji '.$roleName,
            'email' => str(Str::slug($roleName)).'-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    public function test_dashboard_jabatan_menampilkan_header_filter_dan_kpi(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');

        Student::create([
            'school_id' => $user->employment->school_id,
            'name' => 'Santri Uji',
            'nisn' => '1234567890',
            'gender' => 'L',
            'status' => 'active',
            'entry_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard.satuan-pendidikan', ['userId' => $user->id]));

        $response->assertOk();
        $response->assertSee('Tahun Ajaran', false);
        $response->assertSee('Santri Aktif', false);
        $response->assertSee('Temuan &amp; Perlu Perhatian', false);

        // Pemilih dashboard lain dan teks pengantar tidak diperlukan.
        $response->assertDontSee('Dashboard Lain', false);
        $response->assertDontSee('Ringkasan operasional, tren, dan tindak lanjut sesuai jabatan Anda.', false);

        // Satu indikator hanya boleh tampil satu kali.
        $response->assertSee('Tren Kehadiran Santri (14 Hari)', false);
        $response->assertDontSee('Tren Kehadiran Santri (7 Hari)', false);
        $response->assertSee('Distribusi Santri per Jenjang', false);
        $response->assertDontSee('Rekap Santri per Tingkat', false);
        $response->assertDontSee('Top 5 Rombel Terbesar', false);
        $response->assertDontSee('Statistik Santri per Rombel', false);
    }

    public function test_dashboard_memakai_salam_islami_dan_kaldik_bulan_berjalan(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');
        $filters = app(DashboardContextService::class)
            ->filters(Request::create('/', 'GET'), $user);

        DB::table('kaldik')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Kegiatan Bulan Ini',
            'category' => 'kaldik',
            'visibility' => 'satuan',
            'type' => 'kegiatan',
            'color' => '#3B82F6',
            'academic_year_id' => $filters['academic_year_id'],
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addDay()->toDateString(),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('kaldik')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Kegiatan Bulan Depan',
            'category' => 'kaldik',
            'visibility' => 'satuan',
            'type' => 'kegiatan',
            'color' => '#F59E0B',
            'academic_year_id' => $filters['academic_year_id'],
            'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard.satuan-pendidikan', ['userId' => $user->id]));

        $response->assertOk();
        $response->assertSee('welcome-banner', false);
        $response->assertSee('Assalamu&#039;alaikum warahmatullahi wabarakatuh', false);
        $response->assertDontSee('Selamat pagi');
        $response->assertSee('Kalender Pendidikan', false);
        $response->assertSee('Kegiatan Bulan Ini', false);
        $response->assertDontSee('Kegiatan Bulan Depan', false);
        // Satu sekolah satu jenis kelamin: grafik gender tidak lagi ditampilkan.
        $response->assertDontSee('Sebaran Jenis Kelamin', false);
    }

    public function test_jadwal_shalat_memakai_kota_unit_dan_menandai_shalat_berikutnya(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');

        $schoolId = (string) DB::table('gtk_employments')->where('user_id', $user->id)->value('school_id');
        DB::table('schools')->where('id', $schoolId)->update([
            'name' => 'Sekolah Uji Mataram',
        ]);

        $user = $user->fresh();
        $filters = app(DashboardContextService::class)
            ->filters(Request::create('/', 'GET'), $user);
        $this->assertNotNull($filters['school'], 'Unit pengguna harus terdeteksi.');
        $this->assertSame($schoolId, $filters['school']->id);

        Cache::flush();

        Http::fake([
            'api.aladhan.com/*' => Http::response([
                'code' => 200,
                'data' => [
                    'timings' => [
                        'Fajr' => '04:28', 'Dhuhr' => '11:59', 'Asr' => '15:11',
                        'Maghrib' => '18:11', 'Isha' => '19:23',
                    ],
                    'date' => ['hijri' => ['day' => '17', 'month' => ['en' => 'Jumada al-Ula'], 'year' => '1448']],
                ],
            ], 200),
        ]);

        // Waktu dibekukan agar penanda "shalat berikutnya" deterministik.
        $this->travelTo(Carbon::parse('2026-10-28 03:00:00'));

        try {
            $prayer = app(DashboardCalendarService::class)->prayerTimes($filters);

            $this->assertTrue($prayer['available'], json_encode($prayer));
            $this->assertSame('Mataram', $prayer['city']);
            $this->assertCount(5, $prayer['times']);
            $this->assertSame(['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'], array_column($prayer['times'], 'label'));
            $this->assertSame(['label' => 'Subuh', 'time' => '04:28'], $prayer['next']);
        } finally {
            $this->travelBack();
        }
    }

    public function test_jadwal_shalat_tidak_mengarang_angka_saat_api_gagal(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');
        $schoolId = (string) DB::table('gtk_employments')->where('user_id', $user->id)->value('school_id');
        DB::table('schools')->where('id', $schoolId)->update([
            'name' => 'Sekolah Uji Mataram',
        ]);

        Http::fake([
            'api.aladhan.com/*' => Http::response(null, 500),
        ]);
        Cache::flush();

        $filters = app(DashboardContextService::class)
            ->filters(Request::create('/', 'GET'), $user->fresh());

        $prayer = app(DashboardCalendarService::class)->prayerTimes($filters);

        $this->assertFalse($prayer['available']);
        $this->assertSame([], $prayer['times']);
        $this->assertNull($prayer['next']);
    }

    public function test_dashboard_menampilkan_carousel_info_dari_pengumuman_terbit(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');

        $announcement = HumasAnnouncement::create([
            'school_id' => $user->employment->school_id,
            'target_type' => 'school',
            'title' => 'Informasi Uji Carousel',
            'category' => 'umum',
            'severity' => 'info',
            'recipient_group' => 'both',
            'audience' => 'all',
            'content' => 'Isi informasi',
            'excerpt' => 'Ringkasan informasi',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        HumasAnnouncementImage::create([
            'humas_announcement_id' => $announcement->id,
            'path' => 'info/uji.jpg',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard.satuan-pendidikan', ['userId' => $user->id]));

        $response->assertOk();
        $response->assertSee('Info Terbaru', false);
        $response->assertSee('Informasi Uji Carousel', false);
    }

    public function test_dashboard_menampilkan_modal_broadcast_dan_mencatat_konfirmasi(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');
        $publisher = $this->makeUser('Humas Personalia');

        $broadcast = HumasAnnouncement::create([
            'school_id' => $user->employment->school_id,
            'target_type' => 'school',
            'title' => 'Broadcast Wajib Baca',
            'category' => 'broadcast',
            'severity' => 'urgent',
            'recipient_group' => 'gtk',
            'audience' => 'gtk',
            'content' => 'Harap dibaca',
            'status' => 'published',
            'ack_required' => true,
            'published_at' => now(),
            'created_by' => $publisher->id,
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard.satuan-pendidikan', ['userId' => $user->id]));
        $response->assertOk();
        $response->assertSee('Broadcast Wajib Baca', false);

        $ack = $this->actingAs($user)->postJson(route('user.dashboard.broadcasts.acknowledge', [
            'userId' => $user->id,
            'announcementId' => $broadcast->id,
        ]));

        $ack->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('announcement_acknowledgements', [
            'reference_id' => $broadcast->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_filter_dashboard_membatasi_data_unit_lain(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');
        $otherSchool = School::create([
            'work_unit_id' => $user->employment->school_id ? DB::table('schools')->where('id', $user->employment->school_id)->value('work_unit_id') : null,
            'npsn' => '88888888',
            'name' => 'Sekolah Lain',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard.satuan-pendidikan', [
            'userId' => $user->id,
            'school_id' => $otherSchool->id,
        ]));

        $response->assertOk();
        // Unit terkunci: parameter unit lain diabaikan, bukan membocorkan data.
        $response->assertSee($user->employment->school->name ?? 'Semua unit', false);
    }

    public function test_ekspor_dashboard_menghasilkan_excel_dan_pdf(): void
    {
        $user = $this->makeUser('Satuan Pendidikan');

        $excel = $this->actingAs($user)->get(route('user.dashboard.export', [
            'userId' => $user->id, 'role' => 'satuan-pendidikan',
        ]));
        $excel->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $excel->headers->get('content-type'));

        $pdf = $this->actingAs($user)->get(route('user.dashboard.export.pdf', [
            'userId' => $user->id, 'role' => 'satuan-pendidikan',
        ]));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $pdf->getContent());
    }
}
