<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Registrasi perangkat mobile untuk push notification native.
 *
 * Android mengirim token FCM, iOS mengirim token APNs. Keduanya berasal dari
 * `expo-notifications.getDevicePushTokenAsync()`.
 */
class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (UserDevice $device) => $this->formatDevice($device));

        return response()->json([
            'success' => true,
            'data' => ['devices' => $devices],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'push_token' => 'required|string|min:10|max:512',
            'provider' => 'required|string|in:fcm,apns',
            'platform' => 'required|string|in:android,ios',
            'device_name' => 'nullable|string|max:120',
            'app_version' => 'nullable|string|max:40',
            'device_id' => 'nullable|string|max:191',
        ]);

        $platform = strtolower((string) $data['platform']);
        $provider = strtolower((string) $data['provider']);
        $pushToken = trim((string) $data['push_token']);

        if (($platform === 'android' && $provider !== 'fcm')
            || ($platform === 'ios' && $provider !== 'apns')) {
            throw ValidationException::withMessages([
                'provider' => ['Provider harus fcm untuk Android dan apns untuk iOS.'],
            ]);
        }

        $device = UserDevice::query()
            ->where('push_token', $pushToken)
            ->first();

        $isNew = $device === null;

        if ($device === null) {
            $device = new UserDevice(['push_token' => $pushToken]);
        }

        // Satu instalasi hanya boleh terhubung ke akun yang sedang login.
        // Jika perangkat berpindah akun, token dipindahkan agar push tidak
        // terkirim ke akun sebelumnya.
        $device->fill([
            'user_id' => $request->user()->id,
            'provider' => $provider,
            'platform' => $platform,
            'device_name' => $data['device_name'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'last_seen_at' => now(),
            'revoked_at' => null,
        ]);
        $device->save();

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil didaftarkan untuk notifikasi.',
            'data' => ['device' => $this->formatDevice($device)],
        ], $isNew ? 201 : 200);
    }

    public function destroy(Request $request, string $device): JsonResponse
    {
        $record = UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $device)
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEVICE_NOT_FOUND',
                    'message' => 'Perangkat tidak ditemukan.',
                ],
            ], 404);
        }

        if ($record->revoked_at === null) {
            $record->update(['revoked_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perangkat tidak lagi menerima notifikasi.',
            'data' => ['id' => $record->id],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDevice(UserDevice $device): array
    {
        return [
            'id' => $device->id,
            'provider' => $device->provider,
            'platform' => $device->platform,
            'device_name' => $device->device_name,
            'app_version' => $device->app_version,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
        ];
    }
}
