<?php

namespace Tests\Feature\Sarpras;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleFuelLog;
use App\Models\VehicleServiceLog;
use App\Models\VehicleTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class SarprasVehicleTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected User $admin;
    protected string $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'name' => 'Admin Armada URT',
            'is_system_admin' => true,
        ]);
        $this->schoolId = $this->createSchool()->id;
    }

    private function url(string $path = 'armada'): string
    {
        return "/{$this->admin->id}/sarpras/{$path}";
    }

    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'plate_number' => 'B 1234 TEST',
            'name' => 'Mobil Operasional',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'type' => 'mobil',
            'capacity' => 7,
            'status' => 'tersedia',
            'odometer' => 12000,
            'notes' => 'Kendaraan test',
        ], $overrides);
    }

    /** @test */
    public function admin_can_open_all_armada_pages(): void
    {
        foreach (['armada', 'armada/perjalanan', 'armada/bbm', 'armada/servis'] as $path) {
            $this->actingAs($this->admin)
                ->get($this->url($path))
                ->assertOk();
        }
    }

    /** @test */
    public function admin_can_create_update_toggle_and_delete_vehicle(): void
    {
        $this->actingAs($this->admin)
            ->post($this->url('armada'), $this->vehiclePayload())
            ->assertRedirect();

        $vehicle = Vehicle::where('plate_number', 'B 1234 TEST')->firstOrFail();
        $this->assertSame($this->schoolId, $vehicle->school_id);

        $this->actingAs($this->admin)
            ->put($this->url("armada/{$vehicle->id}"), $this->vehiclePayload([
                'plate_number' => 'B 5678 TEST',
                'name' => 'Mobil Operasional Baru',
                'status' => 'perawatan',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'plate_number' => 'B 5678 TEST',
            'name' => 'Mobil Operasional Baru',
            'status' => 'perawatan',
        ]);

        $this->actingAs($this->admin)
            ->post($this->url("armada/{$vehicle->id}/toggle"))
            ->assertRedirect();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'is_active' => false]);

        $this->actingAs($this->admin)
            ->delete($this->url("armada/{$vehicle->id}"))
            ->assertRedirect();
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    /** @test */
    public function admin_can_manage_trip_fuel_and_service_logs(): void
    {
        $vehicle = Vehicle::create([
            ...$this->vehiclePayload(['plate_number' => 'B 9999 TEST']),
            'school_id' => $this->schoolId,
        ]);

        $this->actingAs($this->admin)
            ->post($this->url('armada/perjalanan'), [
                'vehicle_id' => $vehicle->id,
                'destination' => 'Kantor Cabang',
                'purpose' => 'Pengantaran dokumen',
                'passengers' => 3,
                'departure_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'odometer_start' => 12050,
            ])
            ->assertRedirect();

        $trip = VehicleTrip::where('vehicle_id', $vehicle->id)->firstOrFail();
        $this->assertSame('dijadwalkan', $trip->status);

        $this->actingAs($this->admin)
            ->put($this->url("armada/perjalanan/{$trip->id}"), [
                'status' => 'berangkat',
                'odometer_start' => 12050,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'digunakan']);

        $this->actingAs($this->admin)
            ->post($this->url('armada/bbm'), [
                'vehicle_id' => $vehicle->id,
                'fuel_date' => now()->toDateString(),
                'fuel_type' => 'pertalite',
                'liters' => 35.5,
                'cost' => 450000,
                'odometer' => 12100,
                'receipt_no' => 'BBM-TEST-01',
            ])
            ->assertRedirect();

        $fuel = VehicleFuelLog::where('vehicle_id', $vehicle->id)->firstOrFail();
        $this->assertSame(12100, $vehicle->fresh()->odometer);

        $this->actingAs($this->admin)
            ->delete($this->url("armada/bbm/{$fuel->id}"))
            ->assertRedirect();
        $this->assertDatabaseMissing('vehicle_fuel_logs', ['id' => $fuel->id]);

        $this->actingAs($this->admin)
            ->post($this->url('armada/servis'), [
                'vehicle_id' => $vehicle->id,
                'service_type' => 'ganti_oli',
                'service_date' => now()->toDateString(),
                'cost' => 250000,
                'vendor_name' => 'Bengkel Test',
                'next_service_date' => now()->addMonths(6)->toDateString(),
                'status' => 'selesai',
                'notes' => 'Servis berkala',
            ])
            ->assertRedirect();

        $service = VehicleServiceLog::where('vehicle_id', $vehicle->id)->firstOrFail();
        $this->assertSame('tersedia', $vehicle->fresh()->status);
        $this->assertSame(now()->toDateString(), $vehicle->fresh()->last_service_date->toDateString());

        $this->actingAs($this->admin)
            ->delete($this->url("armada/servis/{$service->id}"))
            ->assertRedirect();
        $this->assertDatabaseMissing('vehicle_service_logs', ['id' => $service->id]);

        $this->actingAs($this->admin)
            ->delete($this->url("armada/perjalanan/{$trip->id}"))
            ->assertRedirect();
        $this->assertDatabaseMissing('vehicle_trips', ['id' => $trip->id]);
    }
}
