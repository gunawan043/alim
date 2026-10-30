<?php

namespace App\Http\Controllers;

use App\Models\WebPushSubscription;
use App\Support\WebPushConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebPushSubscriptionController extends Controller
{
    public function publicKey(Request $request): JsonResponse
    {
        $publicKey = WebPushConfig::vapidPublicKey();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $publicKey !== '',
                'public_key' => $publicKey,
            ],
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        if (WebPushConfig::vapidPublicKey() === '') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WEB_PUSH_NOT_CONFIGURED',
                    'message' => 'Web Push belum dikonfigurasi.',
                ],
            ], 503);
        }

        $validated = $request->validate([
            'endpoint' => 'required|url|max:500',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
            'content_encoding' => 'nullable|in:aesgcm,aes128gcm',
        ]);

        $subscription = WebPushSubscription::query()
            ->where('endpoint', $validated['endpoint'])
            ->first();

        $isNew = $subscription === null;

        if ($subscription === null) {
            $subscription = new WebPushSubscription([
                'endpoint' => $validated['endpoint'],
            ]);
        }

        $subscription->fill([
            'user_id' => $request->user()->id,
            'p256dh' => $validated['keys']['p256dh'],
            'auth_token' => $validated['keys']['auth'],
            'content_encoding' => $validated['content_encoding'] ?? 'aes128gcm',
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'last_seen_at' => now(),
            'revoked_at' => null,
        ]);
        $subscription->save();

        return response()->json([
            'success' => true,
            'message' => 'Browser berhasil didaftarkan untuk notifikasi.',
            'data' => [
                'id' => $subscription->id,
                'endpoint' => $subscription->endpoint,
            ],
        ], $isNew ? 201 : 200);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url|max:500',
        ]);

        $subscription = WebPushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if ($subscription) {
            $subscription->update(['revoked_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Browser tidak lagi menerima notifikasi.',
        ]);
    }
}
