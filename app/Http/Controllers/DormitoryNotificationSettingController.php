<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryNotificationSetting;
use Illuminate\Http\Request;

/**
 * Pengaturan pengingat otomatis santri belum kembali ke asrama.
 * Dapat diatur per asrama: interval, jumlah pengingat, kanal, dan eskalasi.
 */
class DormitoryNotificationSettingController extends Controller
{
    public function edit(string $userId, string $asramaUuid)
    {
        $dormitory = $this->resolveDormitory($asramaUuid);
        $setting = DormitoryNotificationSetting::forDormitory($asramaUuid);

        return view('dormitory.settings.notifications', [
            'userId' => $userId,
            'dormitory' => $dormitory,
            'setting' => $setting,
            'channelLabels' => DormitoryNotificationSetting::CHANNEL_LABELS,
        ]);
    }

    public function update(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->resolveDormitory($asramaUuid);

        $data = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'h1_reminder_enabled' => 'nullable|boolean',
            'h1_reminder_time' => 'required|date_format:H:i',
            'interval_minutes' => 'required|integer|min:5|max:1440',
            'max_reminders' => 'required|integer|min:1|max:20',
            'escalate_after_minutes' => 'required|integer|min:15|max:10080',
            'notify_staff' => 'nullable|boolean',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:whatsapp,push,email,in_app',
            'message_template' => 'nullable|string|max:1000',
        ], [
            'channels.required' => 'Pilih minimal satu kanal notifikasi.',
            'channels.min' => 'Pilih minimal satu kanal notifikasi.',
            'interval_minutes.min' => 'Interval minimal 5 menit.',
            'max_reminders.min' => 'Minimal 1 kali pengingat.',
            'h1_reminder_time.required' => 'Jam pengingat H-1 wajib diisi.',
        ]);

        $setting = DormitoryNotificationSetting::firstOrNew(['dormitory_id' => $asramaUuid]);

        $setting->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'h1_reminder_enabled' => $request->boolean('h1_reminder_enabled'),
            'h1_reminder_time' => $data['h1_reminder_time'],
            'interval_minutes' => (int) $data['interval_minutes'],
            'max_reminders' => (int) $data['max_reminders'],
            'escalate_after_minutes' => (int) $data['escalate_after_minutes'],
            'notify_staff' => $request->boolean('notify_staff'),
            'channels' => array_values(array_unique($data['channels'])),
            'message_template' => $data['message_template'] ?? null,
        ]);

        $setting->save();

        return redirect()
            ->route('user.asrama.notifications.edit', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Pengaturan notifikasi kembali asrama berhasil disimpan.');
    }

    private function resolveDormitory(string $asramaUuid): Dormitory
    {
        $user = request()->user();

        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        return Dormitory::findOrFail($asramaUuid);
    }
}
