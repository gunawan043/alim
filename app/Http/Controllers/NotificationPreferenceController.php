<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request, string $userId)
    {
        $this->authorizeSelf($request, $userId);
        $prefs = NotificationPreference::forUser($request->user());

        return view('notifications.preferences', compact('userId', 'prefs'));
    }

    public function update(Request $request, string $userId)
    {
        $this->authorizeSelf($request, $userId);

        $prefs = NotificationPreference::forUser($request->user());

        $prefs->update([
            'email_enabled' => $request->boolean('email_enabled'),
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'push_enabled' => $request->boolean('push_enabled'),
            'lesson_reminder' => $request->boolean('lesson_reminder'),
            'late_notification' => $request->boolean('late_notification'),
        ]);

        return back()->with('success', 'Preferensi notifikasi berhasil disimpan.');
    }

    private function authorizeSelf(Request $request, string $userId): void
    {
        if ((string) $request->user()?->id !== (string) $userId) {
            abort(403, 'Anda hanya dapat mengatur preferensi notifikasi milik sendiri.');
        }
    }
}
