<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemSettingController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $tab = $request->tab ?? 'general';

        $settings = SystemSetting::pluck('value', 'key')->toArray();

        return view('super-admin.system-settings.index', compact('settings', 'tab', 'userId'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name'              => 'nullable|string|max:255',
            'app_tagline'           => 'nullable|string|max:255',
            'app_url'               => 'nullable|url|max:500',
            'app_email'             => 'nullable|email|max:255',
            'app_phone'             => 'nullable|string|max:50',
            'app_address'           => 'nullable|string|max:500',
            'timezone'              => 'nullable|string|max:100',
            'date_format'           => 'nullable|string|max:50',
            'pagination_default'    => 'nullable|integer|min:5|max:100',
            'maintenance_mode'      => 'boolean',
            'registration_enabled'  => 'boolean',
            'otp_expiry_minutes'    => 'nullable|integer|min:1|max:60',
            'max_login_attempts'    => 'nullable|integer|min:1|max:20',
            'lockout_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'session_lifetime_minutes' => 'nullable|integer|min:1|max:10080',
            'email_notifications'   => 'boolean',
            'whatsapp_notifications' => 'boolean',
            'push_notifications'    => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear related caches
        Cache::forget('system_settings');

        return redirect()->back()
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function clearCache()
    {
        Cache::flush();

        return redirect()->back()
            ->with('success', 'Cache berhasil dibersihkan.');
    }
}
