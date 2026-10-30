<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleFuelLog;
use App\Models\VehicleServiceLog;
use App\Models\VehicleTrip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Transportasi / Armada Unit Rumah Tangga.
 *
 * Menutup 4 menu sidebar URT yang sebelumnya placeholder "Soon":
 * Daftar Kendaraan, Jadwal Perjalanan, Log BBM, dan Servis Rutin.
 *
 * Struktural URT mengelola; driver dapat melihat dan mencatat.
 */
class SarprasVehicleController extends SarprasBaseController
{
    // ─────────────────────────────────────────────────────────────
    // DAFTAR KENDARAAN
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authorizeVehicleView($request);

        $vehicles = $this->vehicleQuery($request)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Vehicle::where('is_active', true)->count(),
            'tersedia' => Vehicle::where('status', 'tersedia')->where('is_active', true)->count(),
            'perawatan' => Vehicle::where('status', 'perawatan')->count(),
            'servis_terjadwal' => Vehicle::whereNotNull('next_service_date')
                ->where('next_service_date', '<=', now()->toDateString())
                ->count(),
        ];

        $drivers = $this->driverOptions();

        return view('sarpras.armada.index', compact('vehicles', 'stats', 'drivers'));
    }

    public function store(Request $request)
    {
        $this->authorizeVehicleManage();

        $validated = $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'name' => 'required|string|max:100',
            'brand' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:60',
            'type' => 'required|in:'.implode(',', Vehicle::TYPE_OPTIONS),
            'capacity' => 'nullable|integer|min:0|max:200',
            'status' => 'required|in:'.implode(',', Vehicle::STATUS_OPTIONS),
            'driver_id' => 'nullable|uuid|exists:users,id',
            'last_service_date' => 'nullable|date',
            'next_service_date' => 'nullable|date',
            'odometer' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        Vehicle::create([
            ...$validated,
            'school_id' => $this->resolveSchoolContextId($request),
            'is_active' => true,
        ]);

        return back()->with('success', 'Kendaraan '.$validated['plate_number'].' berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $vehicleId)
    {
        $this->authorizeVehicleManage();
        $vehicle = Vehicle::findOrFail($vehicleId);

        $validated = $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,'.$vehicle->id,
            'name' => 'required|string|max:100',
            'brand' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:60',
            'type' => 'required|in:'.implode(',', Vehicle::TYPE_OPTIONS),
            'capacity' => 'nullable|integer|min:0|max:200',
            'status' => 'required|in:'.implode(',', Vehicle::STATUS_OPTIONS),
            'driver_id' => 'nullable|uuid|exists:users,id',
            'last_service_date' => 'nullable|date',
            'next_service_date' => 'nullable|date',
            'odometer' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $vehicle->update($validated);

        return back()->with('success', 'Data kendaraan diperbarui.');
    }

    public function toggle(string $userId, string $vehicleId)
    {
        $this->authorizeVehicleManage();
        $vehicle = Vehicle::findOrFail($vehicleId);
        $vehicle->update(['is_active' => ! $vehicle->is_active]);

        return back()->with('success', 'Status aktif kendaraan diperbarui.');
    }

    public function destroy(string $userId, string $vehicleId)
    {
        $this->authorizeVehicleManage();
        $vehicle = Vehicle::findOrFail($vehicleId);

        if ($vehicle->trips()->exists() || $vehicle->fuelLogs()->exists() || $vehicle->serviceLogs()->exists()) {
            $vehicle->update(['is_active' => false, 'status' => 'nonaktif']);

            return back()->with('success', 'Kendaraan punya riwayat, jadi dinonaktifkan agar histori tetap aman.');
        }

        $vehicle->delete();

        return redirect()->route('sarpras.armada.index')->with('success', 'Kendaraan dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // JADWAL PERJALANAN
    // ─────────────────────────────────────────────────────────────

    public function trips(Request $request)
    {
        $this->authorizeVehicleView($request);

        $trips = VehicleTrip::with(['vehicle', 'driver'])
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->filled('vehicle_id'), fn (Builder $q) => $q->where('vehicle_id', $request->input('vehicle_id')))
            ->orderByDesc('departure_at')
            ->paginate(20)
            ->withQueryString();

        return view('sarpras.armada.trips', [
            'trips' => $trips,
            'vehicles' => $this->vehicleOptions(),
            'drivers' => $this->driverOptions(),
            'stats' => [
                'dijadwalkan' => VehicleTrip::where('status', 'dijadwalkan')->count(),
                'berjalan' => VehicleTrip::where('status', 'berangkat')->count(),
                'selesai_bulan' => VehicleTrip::where('status', 'selesai')
                    ->whereMonth('departure_at', now()->month)->count(),
            ],
        ]);
    }

    public function storeTrip(Request $request)
    {
        $this->authorizeVehicleManage();

        $validated = $request->validate([
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'driver_id' => 'nullable|uuid|exists:users,id',
            'destination' => 'required|string|max:191',
            'purpose' => 'nullable|string|max:191',
            'passengers' => 'nullable|integer|min:0|max:200',
            'departure_at' => 'nullable|date',
            'return_at' => 'nullable|date',
            'odometer_start' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        VehicleTrip::create([
            ...$validated,
            'school_id' => $this->resolveSchoolContextId($request),
            'status' => 'dijadwalkan',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Jadwal perjalanan ditambahkan.');
    }

    public function updateTrip(Request $request, string $userId, string $tripId)
    {
        $this->authorizeVehicleManage();
        $trip = VehicleTrip::findOrFail($tripId);

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', VehicleTrip::STATUS_OPTIONS),
            'odometer_start' => 'nullable|integer|min:0',
            'odometer_end' => 'nullable|integer|min:0',
            'return_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $trip->update($validated);

        if ($trip->vehicle) {
            $nextStatus = match ($validated['status']) {
                'berangkat' => 'digunakan',
                'selesai', 'batal' => 'tersedia',
                default => $trip->vehicle->status,
            };

            $trip->vehicle->update(['status' => $nextStatus]);
        }

        return back()->with('success', 'Status perjalanan diperbarui.');
    }

    public function destroyTrip(string $userId, string $tripId)
    {
        $this->authorizeVehicleManage();
        VehicleTrip::findOrFail($tripId)->delete();

        return back()->with('success', 'Jadwal perjalanan dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // LOG BBM
    // ─────────────────────────────────────────────────────────────

    public function fuel(Request $request)
    {
        $this->authorizeVehicleView($request);

        $month = $this->resolveMonth($request);

        $logs = VehicleFuelLog::with('vehicle')
            ->when($request->filled('vehicle_id'), fn (Builder $q) => $q->where('vehicle_id', $request->input('vehicle_id')))
            ->whereYear('fuel_date', $month->year)
            ->whereMonth('fuel_date', $month->month)
            ->orderByDesc('fuel_date')
            ->paginate(20)
            ->withQueryString();

        $summary = VehicleFuelLog::query()
            ->when($request->filled('vehicle_id'), fn (Builder $q) => $q->where('vehicle_id', $request->input('vehicle_id')))
            ->whereYear('fuel_date', $month->year)
            ->whereMonth('fuel_date', $month->month)
            ->selectRaw('COUNT(*) as fills, COALESCE(SUM(liters),0) as liters, COALESCE(SUM(cost),0) as cost')
            ->first();

        return view('sarpras.armada.fuel', [
            'logs' => $logs,
            'vehicles' => $this->vehicleOptions(),
            'month' => $month->format('Y-m'),
            'summary' => $summary,
        ]);
    }

    public function storeFuel(Request $request)
    {
        $this->authorizeVehicleManage();

        $validated = $request->validate([
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'fuel_date' => 'required|date',
            'fuel_type' => 'required|in:'.implode(',', VehicleFuelLog::TYPE_OPTIONS),
            'liters' => 'required|numeric|min:0.01|max:99999',
            'cost' => 'nullable|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'receipt_no' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request) {
            VehicleFuelLog::create([
                ...$validated,
                'school_id' => $this->resolveSchoolContextId($request),
                'recorded_by' => auth()->id(),
            ]);

            if (! empty($validated['odometer'])) {
                Vehicle::whereKey($validated['vehicle_id'])
                    ->where(fn (Builder $q) => $q->whereNull('odometer')->orWhere('odometer', '<', $validated['odometer']))
                    ->update(['odometer' => $validated['odometer']]);
            }
        });

        return back()->with('success', 'Pengisian BBM dicatat.');
    }

    public function destroyFuel(string $userId, string $fuelId)
    {
        $this->authorizeVehicleManage();
        VehicleFuelLog::findOrFail($fuelId)->delete();

        return back()->with('success', 'Catatan BBM dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // SERVIS RUTIN
    // ─────────────────────────────────────────────────────────────

    public function service(Request $request)
    {
        $this->authorizeVehicleView($request);

        $services = VehicleServiceLog::with('vehicle')
            ->when($request->filled('vehicle_id'), fn (Builder $q) => $q->where('vehicle_id', $request->input('vehicle_id')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->orderByDesc('service_date')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'selesai' => VehicleServiceLog::where('status', 'selesai')->count(),
            'terjadwal' => VehicleServiceLog::where('status', 'terjadwal')->count(),
            'biaya_bulan' => (float) VehicleServiceLog::whereYear('service_date', now()->year)
                ->whereMonth('service_date', now()->month)->sum('cost'),
        ];

        return view('sarpras.armada.service', [
            'services' => $services,
            'vehicles' => $this->vehicleOptions(),
            'stats' => $stats,
        ]);
    }

    public function storeService(Request $request)
    {
        $this->authorizeVehicleManage();

        $validated = $request->validate([
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'service_type' => 'required|in:'.implode(',', VehicleServiceLog::TYPE_OPTIONS),
            'service_date' => 'required|date',
            'odometer' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'vendor_name' => 'nullable|string|max:191',
            'items_replaced' => 'nullable|string|max:2000',
            'next_service_date' => 'nullable|date',
            'status' => 'required|in:'.implode(',', VehicleServiceLog::STATUS_OPTIONS),
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $request) {
            VehicleServiceLog::create([
                ...$validated,
                'school_id' => $this->resolveSchoolContextId($request),
                'recorded_by' => auth()->id(),
            ]);

            if ($validated['status'] === 'selesai') {
                Vehicle::whereKey($validated['vehicle_id'])->update([
                    'last_service_date' => $validated['service_date'],
                    'next_service_date' => $validated['next_service_date'] ?? null,
                    'status' => 'tersedia',
                ]);
            }
        });

        return back()->with('success', 'Catatan servis kendaraan disimpan.');
    }

    public function destroyService(string $userId, string $serviceId)
    {
        $this->authorizeVehicleManage();
        VehicleServiceLog::findOrFail($serviceId)->delete();

        return back()->with('success', 'Catatan servis dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────

    protected function vehicleQuery(Request $request): Builder
    {
        return Vehicle::with('driver')
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->input('type')))
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = (string) $request->input('q');

                return $q->where(fn (Builder $w) => $w
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('plate_number', 'like', "%{$term}%")
                    ->orWhere('brand', 'like', "%{$term}%"));
            });
    }

    /**
     * @return \Illuminate\Support\Collection<int, Vehicle>
     */
    protected function vehicleOptions()
    {
        return Vehicle::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'plate_number']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function driverOptions()
    {
        $drivers = User::query()
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereHas('roles', fn ($role) => $role->whereIn('name', ['Unit Rumah Tangga', 'Driver', 'Pengemudi']))
                    ->orWhereHas('gtkWorkUnits', fn ($gtk) => $gtk->where('jabatan', 'like', '%PENGEMUDI%'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return $drivers->isNotEmpty()
            ? $drivers
            : User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    protected function resolveMonth(Request $request)
    {
        $raw = (string) $request->input('month', now()->format('Y-m'));

        try {
            return \Illuminate\Support\Carbon::parse($raw.'-01');
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }

    /**
     * Siapa pun yang dapat membuka modul Sarpras boleh melihat armada.
     */
    protected function authorizeVehicleView(Request $request): void
    {
        $user = auth()->user();

        abort_unless((bool) $user, 403);

        if ($this->isCentralSarpras($user) || $this->isUrtLeadership($user)) {
            return;
        }

        $jabatan = strtoupper($this->currentJabatan());

        $allowed = ['PENGEMUDI', 'DRIVER', 'KEBERSIHAN', 'PERTAMANAN', 'TEKNISI', 'RUMAH TANGGA', 'SARPRAS'];

        foreach ($allowed as $keyword) {
            if (str_contains($jabatan, $keyword)) {
                return;
            }
        }

        abort_unless($this->accessibleWorkUnitIds($user) !== [], 403, 'Anda tidak memiliki akses ke modul armada.');
    }

    /**
     * Hanya struktural URT yang boleh mengubah data armada.
     */
    protected function authorizeVehicleManage(): void
    {
        $this->authorizeCentralSarpras();
    }

    protected function currentJabatan(): string
    {
        $user = auth()->user();

        if (! $user) {
            return '';
        }

        $employment = $user->employment?->jabatan ?? '';

        if ($employment !== '') {
            return (string) $employment;
        }

        return (string) \App\Models\GtkWorkUnit::where('user_id', $user->id)->pluck('jabatan')->implode(' ');
    }
}
