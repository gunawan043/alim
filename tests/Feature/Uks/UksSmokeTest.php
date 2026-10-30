<?php

namespace Tests\Feature\Uks;

use App\Models\GtkHealthRecord;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\Uks\UksPatient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UksSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_uks_role_can_access_dashboard(): void
    {
        $role = Role::firstOrCreate(['name' => 'UKS'], ['level' => 22]);
        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('user.uks.dashboard', ['userId' => $user->id]))
            ->assertOk();
    }

    public function test_uks_simple_mode_hides_clinic_queue_and_clinic_mode_enables_it(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        SystemSetting::set('uks_mode', 'simple', 'uks');
        Cache::forget('system_settings_map');

        $this->actingAs($admin)
            ->get(route('user.uks.clinic.queue', ['userId' => $admin->id]))
            ->assertRedirect(route('user.uks.patients.index', ['userId' => $admin->id]));

        SystemSetting::set('uks_mode', 'clinic', 'uks');
        Cache::forget('system_settings_map');

        $this->actingAs($admin)
            ->get(route('user.uks.clinic.queue', ['userId' => $admin->id]))
            ->assertOk();
    }

    public function test_simple_mode_registers_patient_without_queue_and_starts_initial_exam(): void
    {
        SystemSetting::set('uks_mode', 'simple', 'uks');
        Cache::forget('system_settings_map');

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $school = School::factory()->create();
        $student = Student::create([
            'school_id' => $school->id,
            'nisn' => '0099999999',
            'name' => 'Santri Uji UKS',
            'gender' => 'L',
        ]);

        $this->actingAs($admin)
            ->post(route('user.uks.patients.store', ['userId' => $admin->id]), [
                'student_id' => $student->id,
                'patient_type' => 'rawat',
                'chief_complaint' => 'Demam',
                'triage_level' => 'P3',
            ])
            ->assertRedirect();

        $patient = UksPatient::where('student_id', $student->id)->firstOrFail();

        $this->assertNull($patient->queue_number);
        $this->assertSame(UksPatient::STATUS_TREATED, $patient->status);
        $this->assertSame(UksPatient::QUEUE_IN_PROGRESS, $patient->queue_status);
    }

    public function test_gtk_health_record_writes_both_compatibility_columns(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Compatibility-column write test memerlukan MySQL (skema legacy berbeda di SQLite).');
        }

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('user.uks.gtk-health.records.store', [
                'userId' => $admin->id,
                'gtkUuid' => $target->id,
            ]), [
                'check_date' => now()->toDateString(),
                'weight' => 60,
                'height' => 170,
                'pulse' => 80,
                'temperature' => 36.5,
                'respiration_rate' => 18,
                'blood_pressure' => '120/80',
            ])
            ->assertRedirect();

        $record = GtkHealthRecord::where('user_id', $target->id)->latest()->firstOrFail();

        $this->assertNotNull($record->examined_at);
        $this->assertSame('60.0', (string) $record->weight_kg);
        $this->assertSame('170.0', (string) $record->height_cm);
        $this->assertSame(80, $record->pulse_bpm);
        $this->assertSame('36.5', (string) $record->temperature_c);
    }

    public function test_uks_pages_render_without_server_errors(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $routes = [
            'gtk-index' => route('user.gtk.index', ['userId' => $user->id]),
            'import-medicine' => route('user.uks.imports.form', ['userId' => $user->id, 'dataset' => 'medicine']),
            'import-metrics' => route('user.uks.imports.form', ['userId' => $user->id, 'dataset' => 'metrics']),
            'import-gtk-health' => route('user.uks.imports.form', ['userId' => $user->id, 'dataset' => 'gtk-health']),
            'dashboard' => route('user.uks.dashboard', ['userId' => $user->id]),
            'gtk-health' => route('user.uks.gtk-health.index', ['userId' => $user->id]),
            'profile' => route('user.uks.profile', ['userId' => $user->id]),
            'reports' => route('user.uks.reports.index', ['userId' => $user->id]),
            'reports.attendance' => route('user.uks.reports.attendance', ['userId' => $user->id]),
            'reports.occupancy' => route('user.uks.reports.occupancy', ['userId' => $user->id]),
            'reports.permits' => route('user.uks.reports.permits', ['userId' => $user->id]),
            'reports.sanitation' => route('user.uks.reports.sanitation', ['userId' => $user->id]),
            'reports.violations' => route('user.uks.reports.violations', ['userId' => $user->id]),
            'reports.rewards' => route('user.uks.reports.rewards', ['userId' => $user->id]),
            'reports.inventories' => route('user.uks.reports.inventories', ['userId' => $user->id]),
            'reports.export.csv' => route('user.uks.reports.export', ['userId' => $user->id, 'report' => 'attendance']),
            'reports.export.pdf' => route('user.uks.reports.pdf', ['userId' => $user->id, 'report' => 'attendance']),
            'patients' => route('user.uks.patients.index', ['userId' => $user->id]),
            'medicine-logs' => route('user.uks.medicine-logs.index', ['userId' => $user->id]),
            'beds' => route('user.uks.beds.index', ['userId' => $user->id]),
            'care-locations' => route('user.uks.care-locations.index', ['userId' => $user->id]),
            'health-metrics' => route('user.uks.health-metrics.index', ['userId' => $user->id]),
            'health-metrics.dashboard' => route('user.uks.health-metrics.dashboard', ['userId' => $user->id]),
            'pharmacy' => route('user.uks.pharmacy.index', ['userId' => $user->id]),
            'medicine-inventory' => route('user.uks.medicine-inventory.index', ['userId' => $user->id]),
            'referrals' => route('user.uks.referrals.index', ['userId' => $user->id]),
            'facility-referrals' => route('user.uks.facility-referrals.index', ['userId' => $user->id]),
            'sanitation' => route('user.uks.sanitation-inspections.index', ['userId' => $user->id]),
            'sanitation.dashboard' => route('user.uks.sanitation-inspections.dashboard', ['userId' => $user->id]),
            'scheduling' => route('user.uks.scheduling.index', ['userId' => $user->id]),
        ];

        foreach ($routes as $name => $url) {
            $response = $this->actingAs($user)->get($url);
            $status = method_exists($response, 'status') ? $response->status() : $response->getStatusCode();
            $this->assertSame(200, $status, "{$name} returned {$status}");
        }
    }
}
