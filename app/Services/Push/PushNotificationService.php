<?php

namespace App\Services\Push;

use App\Exceptions\InvalidPushTokenException;
use App\Models\NotificationUniversal;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\ApnsConfig;
use App\Support\FcmConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Dispatcher push native. Memilih FCM untuk Android dan APNs untuk iOS.
 */
class PushNotificationService
{
    public function __construct(
        private FcmService $fcm,
        private ApnsService $apns,
    ) {}

    public function isConfigured(): bool
    {
        return FcmConfig::isConfigured() || ApnsConfig::isConfigured();
    }

    /**
     * @return array{sent:int,failed:int,invalid:int,skipped:int}
     */
    public function sendToUser(User $user, NotificationUniversal $notification): array
    {
        /** @var Collection<int, UserDevice> $devices */
        $devices = UserDevice::query()
            ->where('user_id', $user->id)
            ->active()
            ->get();

        $result = ['sent' => 0, 'failed' => 0, 'invalid' => 0, 'skipped' => 0];

        foreach ($devices as $device) {
            if (! $device->push_token) {
                $result['skipped']++;

                continue;
            }

            try {
                $ok = match ($device->provider) {
                    'fcm' => FcmConfig::isConfigured()
                        ? $this->fcm->send($device, $notification)
                        : null,
                    'apns' => ApnsConfig::isConfigured()
                        ? $this->apns->send($device, $notification)
                        : null,
                    default => null,
                };

                if ($ok === null) {
                    $result['skipped']++;
                } elseif ($ok) {
                    $result['sent']++;
                } else {
                    $result['failed']++;
                }
            } catch (InvalidPushTokenException $e) {
                $device->update(['revoked_at' => now()]);
                $result['invalid']++;
                Log::info('Perangkat push dicabut karena token tidak valid', [
                    'device_id' => $device->id,
                    'provider' => $device->provider,
                    'notification_id' => $notification->id,
                ]);
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('Gagal mengirim push ke perangkat', [
                    'device_id' => $device->id,
                    'provider' => $device->provider,
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }
}
