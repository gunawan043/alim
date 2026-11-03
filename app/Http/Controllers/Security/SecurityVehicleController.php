<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityPost;
use App\Models\Security\SecurityVehicleLog;
use App\Services\Security\SecurityCodeService;
use App\Support\SecurityAccess;
use Illuminate\Http\Request;

class SecurityVehicleController extends Controller
{
    public function __construct(private readonly SecurityCodeService $codes) {}

    public function index(Request $request, string $userId)
    {
        $query = SecurityVehicleLog::query()->with(['post', 'recorder']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('check_in_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('check_in_at', '<=', $to);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', '%'.$search.'%')
                    ->orWhere('driver_name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $vehicles = $query->latest('check_in_at')->paginate(20)->withQueryString();

        $stats = [
            'inside' => SecurityVehicleLog::query()->inside()->count(),
            'today' => SecurityVehicleLog::query()->whereDate('check_in_at', today())->count(),
            'out_today' => SecurityVehicleLog::query()->whereDate('check_out_at', today())->count(),
        ];

        $posts = SecurityPost::query()->where('is_active', true)->orderBy('name')->get();

        return view('security.vehicles.index', compact('userId', 'vehicles', 'stats', 'posts'));
    }

    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20'],
            'vehicle_type' => ['required', 'in:mobil,motor,truk,bus,lainnya'],
            'driver_name' => ['required', 'string', 'max:150'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'max:191'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'plate_number.required' => 'Nomor polisi wajib diisi.',
            'driver_name.required' => 'Nama pengemudi wajib diisi.',
            'purpose.required' => 'Keperluan wajib diisi.',
        ]);

        $postId = $data['post_id'] ?? SecurityAccess::postIdFor($request->user())
            ?? SecurityPost::query()->where('is_active', true)->value('id');

        $vehicle = SecurityVehicleLog::create([
            'post_id' => $postId,
            'code' => $this->codes->vehicle(),
            'plate_number' => strtoupper($data['plate_number']),
            'vehicle_type' => $data['vehicle_type'],
            'driver_name' => $data['driver_name'],
            'driver_phone' => $data['driver_phone'] ?? null,
            'purpose' => $data['purpose'],
            'passenger_count' => $data['passenger_count'] ?? 1,
            'check_in_at' => now(),
            'status' => SecurityVehicleLog::STATUS_INSIDE,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.keamanan.vehicles.index', ['userId' => $userId])
            ->with('success', 'Kendaraan '.$vehicle->plate_number.' tercatat masuk.');
    }

    public function checkout(Request $request, string $userId, string $vehicle)
    {
        $vehicle = SecurityVehicleLog::query()->findOrFail($vehicle);

        if ($vehicle->check_out_at) {
            return back()->with('error', 'Kendaraan ini sudah tercatat keluar.');
        }

        $vehicle->update([
            'check_out_at' => now(),
            'status' => SecurityVehicleLog::STATUS_OUT,
            'checked_out_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.keamanan.vehicles.index', ['userId' => $userId])
            ->with('success', 'Kendaraan '.$vehicle->plate_number.' tercatat keluar.');
    }
}
