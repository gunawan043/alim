<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\Security\SecurityCheckpoint;
use App\Models\Security\SecurityGuestLog;
use App\Models\Security\SecurityIncident;
use App\Models\Security\SecurityPatrolSchedule;
use App\Models\Security\SecurityShift;
use App\Models\Security\SecurityTeam;
use App\Models\Security\SecurityTeamMember;
use App\Models\Security\SecurityVehicleLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Satuan Keamanan', 'guard_name' => 'web']);

        $this->officer = User::factory()->create(['is_active' => true]);
        $this->officer->assignRole($role);
    }

    private function url(string $name, array $params = []): string
    {
        return route($name, array_merge(['userId' => $this->officer->id], $params));
    }

    /**
     * Kepala Satuan Keamanan: punya jabatan (lewat GtkEmployment) + role.
     *
     * Role sengaja dipasang SETELAH employment dibuat karena
     * GtkEmploymentObserver::syncRoles() mengganti seluruh role user.
     */
    private function makeChief(): User
    {
        $chief = User::factory()->create(['is_active' => true]);

        GtkEmployment::create([
            'user_id' => $chief->id,
            'jabatan' => 'Kepala Satuan Keamanan',
            'status_kepegawaian' => 'Tetap',
            'is_active' => true,
        ]);

        $chief->assignRole(Role::firstOrCreate(['name' => 'Satuan Keamanan', 'guard_name' => 'web']));

        return $chief->fresh();
    }

    // ── AKSES ────────────────────────────────────────────────────────────

    public function test_petugas_keamanan_bisa_membuka_dashboard(): void
    {
        $this->actingAs($this->officer)
            ->get($this->url('user.keamanan.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_pengguna_tanpa_role_keamanan_ditolak(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole(Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']));

        $this->actingAs($other)
            ->get(route('user.keamanan.dashboard', ['userId' => $other->id]))
            ->assertForbidden();
    }

    // ── LOG TAMU ─────────────────────────────────────────────────────────

    public function test_registrasi_tamu_mencatat_masuk_dan_bisa_keluar(): void
    {
        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.guests.store'), [
                'visitor_name' => 'Budi Santoso',
                'purpose' => 'Mengantar paket',
                'visitor_count' => 2,
            ])
            ->assertRedirect();

        $guest = SecurityGuestLog::firstOrFail();

        $this->assertSame('di_dalam', $guest->status);
        $this->assertNotNull($guest->check_in_at);
        $this->assertStringStartsWith('TAMU-', $guest->code);

        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.guests.checkout', ['guest' => $guest->id]))
            ->assertRedirect();

        $this->assertSame('keluar', $guest->fresh()->status);
        $this->assertNotNull($guest->fresh()->check_out_at);
    }

    public function test_kartu_tamu_bisa_dicetak(): void
    {
        $guest = SecurityGuestLog::create([
            'code' => 'TAMU-TEST-0001',
            'visitor_name' => 'Ibu Siti',
            'purpose' => 'Menjenguk santri',
            'check_in_at' => now(),
            'status' => SecurityGuestLog::STATUS_INSIDE,
            'recorded_by' => $this->officer->id,
        ]);

        $this->actingAs($this->officer)
            ->get($this->url('user.keamanan.guests.card', ['guest' => $guest->id]))
            ->assertOk()
            ->assertSee('Ibu Siti')
            ->assertSee('TAMU-TEST-0001');

        $this->assertNotNull($guest->fresh()->badge_printed_at);
    }

    // ── LOG KENDARAAN ────────────────────────────────────────────────────

    public function test_kendaraan_masuk_dan_keluar_tercatat(): void
    {
        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.vehicles.store'), [
                'plate_number' => 'dr 1234 ab',
                'vehicle_type' => 'mobil',
                'driver_name' => 'Pak Ahmad',
                'purpose' => 'Antar barang',
            ])
            ->assertRedirect();

        $vehicle = SecurityVehicleLog::firstOrFail();

        $this->assertSame('DR 1234 AB', $vehicle->plate_number);
        $this->assertSame('di_dalam', $vehicle->status);

        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.vehicles.checkout', ['vehicle' => $vehicle->id]))
            ->assertRedirect();

        $this->assertSame('keluar', $vehicle->fresh()->status);
    }

    // ── PATROLI ──────────────────────────────────────────────────────────

    public function test_patroli_selesai_otomatis_setelah_semua_checkpoint_dipindai(): void
    {
        // Shift default sudah dibuat migrasi data awal.
        $shift = SecurityShift::firstOrCreate(
            ['code' => 'S1'],
            ['name' => 'Pagi', 'start_time' => '06:00', 'end_time' => '14:00', 'is_active' => true],
        );

        $team = SecurityTeam::create([
            'code' => 'R-01', 'name' => 'Regu Alpha', 'shift_id' => $shift->id, 'is_active' => true,
        ]);

        SecurityTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->officer->id,
            'position' => 'anggota',
            'start_date' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $checkpoint = SecurityCheckpoint::create([
            'code' => 'CP-01', 'name' => 'Gerbang Utama', 'is_active' => true,
        ]);

        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.patrol.store'), [
                'patrol_date' => today()->toDateString(),
                'team_id' => $team->id,
            ])
            ->assertRedirect();

        $schedule = SecurityPatrolSchedule::firstOrFail();

        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.patrol.scan.store'), [
                'schedule_id' => $schedule->id,
                'checkpoint_id' => $checkpoint->id,
                'condition' => 'aman',
            ])
            ->assertRedirect();

        $this->assertSame(SecurityPatrolSchedule::STATUS_DONE, $schedule->fresh()->status);
        $this->assertDatabaseHas('security_patrol_logs', [
            'schedule_id' => $schedule->id,
            'checkpoint_id' => $checkpoint->id,
        ]);
    }

    public function test_checkpoint_tidak_bisa_dipindai_dua_kali_pada_jadwal_sama(): void
    {
        $checkpoint = SecurityCheckpoint::create(['code' => 'CP-02', 'name' => 'Asrama Putra', 'is_active' => true]);
        $schedule = SecurityPatrolSchedule::create([
            'code' => 'PTR-TEST-0001',
            'patrol_date' => today()->toDateString(),
            'status' => SecurityPatrolSchedule::STATUS_RUNNING,
        ]);

        $payload = [
            'schedule_id' => $schedule->id,
            'checkpoint_id' => $checkpoint->id,
            'condition' => 'aman',
        ];

        $this->actingAs($this->officer)->post($this->url('user.keamanan.patrol.scan.store'), $payload);
        $this->actingAs($this->officer)->post($this->url('user.keamanan.patrol.scan.store'), $payload);

        $this->assertSame(1, $schedule->logs()->count());
    }

    // ── INSIDEN ──────────────────────────────────────────────────────────

    public function test_insiden_dilaporkan_dan_ditutup_oleh_struktural(): void
    {
        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.incidents.store'), [
                'title' => 'Orang tidak dikenal masuk',
                'description' => 'Ditemukan di area parkir pukul 02.00.',
                'occurred_at' => now()->format('Y-m-d H:i:s'),
                'severity' => 'berat',
            ])
            ->assertRedirect();

        $incident = SecurityIncident::firstOrFail();

        $this->assertSame(SecurityIncident::STATUS_NEW, $incident->status);
        $this->assertDatabaseCount('security_incident_actions', 1);

        // Anggota biasa tidak boleh menutup insiden.
        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.incidents.close', ['incident' => $incident->id]), [
                'resolution' => 'Sudah diusir',
            ])
            ->assertForbidden();

        // Kepala Satuan Keamanan boleh.
        $chief = $this->makeChief();

        $this->actingAs($chief)
            ->post(route('user.keamanan.incidents.close', ['userId' => $chief->id, 'incident' => $incident->id]), [
                'resolution' => 'Orang sudah diserahkan ke pihak berwenang.',
            ])
            ->assertRedirect();

        $incident->refresh();
        $this->assertSame(SecurityIncident::STATUS_CLOSED, $incident->status);
        $this->assertNotNull($incident->closed_at);
    }

    // ── REGU & KONFIGURASI ───────────────────────────────────────────────

    public function test_regu_dan_anggota_dapat_dikelola(): void
    {
        $chief = $this->makeChief();

        $this->actingAs($chief)
            ->post(route('user.keamanan.teams.store', ['userId' => $chief->id]), [
                'code' => 'R-09',
                'name' => 'Regu Malam',
            ])
            ->assertRedirect();

        $team = SecurityTeam::where('code', 'R-09')->firstOrFail();

        $this->actingAs($chief)
            ->post(route('user.keamanan.teams.members.store', ['userId' => $chief->id, 'team' => $team->id]), [
                'user_id' => $this->officer->id,
                'position' => 'anggota',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('security_team_members', [
            'team_id' => $team->id,
            'user_id' => $this->officer->id,
        ]);
    }

    public function test_jenis_insiden_dan_kontak_darurat_dapat_ditambah(): void
    {
        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.config.incident-types.store'), [
                'code' => 'INS-PENcurian',
                'name' => 'Pencurian',
                'default_severity' => 'berat',
            ])
            ->assertRedirect();

        $this->actingAs($this->officer)
            ->post($this->url('user.keamanan.config.contacts.store'), [
                'name' => 'Polsek Mataram',
                'phone' => '110',
                'category' => 'eksternal',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('security_incident_types', ['code' => 'INS-PENcurian']);
        $this->assertDatabaseHas('security_contacts', ['name' => 'Polsek Mataram']);
    }

    // ── HALAMAN (render Blade) ───────────────────────────────────────────

    public function test_semua_halaman_utama_dapat_dirender(): void
    {
        $incident = SecurityIncident::create([
            'code' => 'INS-TEST-0001',
            'title' => 'Uji render',
            'description' => 'Deskripsi',
            'occurred_at' => now(),
            'severity' => 'sedang',
            'status' => SecurityIncident::STATUS_NEW,
            'reported_by' => $this->officer->id,
        ]);

        $checkpoint = SecurityCheckpoint::create(['code' => 'CP-09', 'name' => 'Pos Uji', 'is_active' => true]);

        $pages = [
            $this->url('user.keamanan.guests.index'),
            $this->url('user.keamanan.vehicles.index'),
            $this->url('user.keamanan.gate.index'),
            $this->url('user.keamanan.gate.index', ['tab' => 'late']),
            $this->url('user.keamanan.gate.index', ['tab' => 'outside']),
            $this->url('user.keamanan.patrol.index'),
            $this->url('user.keamanan.patrol.checkpoints.index'),
            $this->url('user.keamanan.patrol.scan', ['token' => $checkpoint->qr_token]),
            $this->url('user.keamanan.incidents.index'),
            $this->url('user.keamanan.incidents.create'),
            $this->url('user.keamanan.incidents.show', ['incident' => $incident->id]),
            $this->url('user.keamanan.teams.index'),
            $this->url('user.keamanan.reports.index'),
        ];

        foreach (['pos', 'shift', 'zona', 'jenis', 'kontak', 'sop'] as $tab) {
            $pages[] = $this->url('user.keamanan.config.index', ['tab' => $tab]);
        }

        foreach ($pages as $url) {
            $this->actingAs($this->officer)->get($url)->assertOk();
        }
    }

    // ── LAPORAN ──────────────────────────────────────────────────────────

    public function test_laporan_tamu_dapat_diekspor_csv(): void
    {
        SecurityGuestLog::create([
            'code' => 'TAMU-CSV-0001',
            'visitor_name' => 'Tamu Laporan',
            'purpose' => 'Uji ekspor',
            'check_in_at' => now(),
            'status' => SecurityGuestLog::STATUS_INSIDE,
            'recorded_by' => $this->officer->id,
        ]);

        $response = $this->actingAs($this->officer)
            ->get($this->url('user.keamanan.reports.guests'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
