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

        $waLogs = \Illuminate\Support\Facades\DB::table('wa_message_logs')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('super-admin.system-settings.index', compact('settings', 'tab', 'userId', 'waLogs'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_tagline' => 'nullable|string|max:255',
            'app_url' => 'nullable|url|max:500',
            'app_email' => 'nullable|email|max:255',
            'app_phone' => 'nullable|string|max:50',
            'app_address' => 'nullable|string|max:500',
            'timezone' => 'nullable|string|max:100',
            'date_format' => 'nullable|string|max:50',
            'pagination_default' => 'nullable|integer|min:5|max:100',
            'maintenance_mode' => 'boolean',
            'registration_enabled' => 'boolean',
            'otp_expiry_minutes' => 'nullable|integer|min:1|max:60',
            'max_login_attempts' => 'nullable|integer|min:1|max:20',
            'lockout_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'session_lifetime_minutes' => 'nullable|integer|min:1|max:10080',
            'email_notifications' => 'boolean',
            'whatsapp_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'wa_enabled' => 'boolean',
            'wa_provider' => 'nullable|string|max:30',
            'wa_api_token' => 'nullable|string|max:255',
            'wa_api_url' => 'nullable|string|max:255',
            'wa_sender' => 'nullable|string|max:50',
            'wa_central_number' => 'nullable|string|max:30',
            'wa_delivery_mode' => 'nullable|in:central,guardian,both',
            'wa_template_picked_up' => 'nullable|string|max:2000',
            'wa_template_returned' => 'nullable|string|max:2000',
            'wa_template_sick' => 'nullable|string|max:2000',
            'wa_template_rapor' => 'nullable|string|max:2000',
            'wa_template_holiday' => 'nullable|string|max:2000',
            'wa_event_picked_up' => 'boolean',
            'wa_event_returned' => 'boolean',
            'wa_event_sick' => 'boolean',
            'wa_event_permit_approved' => 'boolean',
            'wa_event_permit_rejected' => 'boolean',
            'wa_event_violation' => 'boolean',
            'wa_event_overdue' => 'boolean',
            'wa_event_rapor' => 'boolean',
            'wa_event_holiday' => 'boolean',
            'wa_event_achievement' => 'boolean',
            'wa_event_announcement' => 'boolean',
            'wa_event_visit' => 'boolean',
        ]);

        // Normalisasi: perbaiki typo umum domain Fonnte + trim token/URL.
        if (array_key_exists('wa_api_url', $validated)) {
            $url = trim((string) $validated['wa_api_url']);
            $url = str_replace(['api.fonte.com', 'fonnte.co/send'], ['api.fonnte.com', 'fonnte.com/send'], $url);
            $validated['wa_api_url'] = $url;
        }
        if (array_key_exists('wa_api_token', $validated)) {
            $validated['wa_api_token'] = trim((string) $validated['wa_api_token']);
        }
        if (array_key_exists('wa_central_number', $validated)) {
            $validated['wa_central_number'] = preg_replace('/\D+/', '', (string) $validated['wa_central_number']);
        }

        // Toggle event: checkbox tidak tercentang = nonaktif.
        foreach (['wa_event_picked_up', 'wa_event_returned', 'wa_event_sick', 'wa_event_permit_approved', 'wa_event_permit_rejected', 'wa_event_violation', 'wa_event_overdue', 'wa_event_rapor', 'wa_event_holiday', 'wa_event_achievement', 'wa_event_announcement', 'wa_event_visit'] as $toggleKey) {
            if (! $request->filled('test_wa') || true) {
                $validated[$toggleKey] = $request->boolean($toggleKey) ? 1 : 0;
            }
        }

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear related caches
        Cache::forget('system_settings');
        Cache::forget('system_settings_map');

        // Cek status device provider (Fonnte) — diagnosa cepat.
        if ($request->filled('check_device')) {
            $info = app(\App\Services\Notifications\WhatsAppGatewayService::class)->checkDevice();

            return redirect()->back()->with(
                !empty($info['ok']) ? 'success' : 'error',
                !empty($info['ok'])
                    ? 'Status device: '.($info['device_status'] ?? '-').' · device '.($info['device'] ?? '-')
                        .' · kuota '.($info['quota'] ?? '-').' · paket '.($info['package'] ?? '-')
                        .' · expired '.($info['expired'] ?? '-')
                    : 'Gagal cek device: '.($info['error'] ?? $info['raw'] ?? 'tidak diketahui')
            );
        }

        // Uji kirim WA ke nomor pusat (opsional dari tombol di tab Notifikasi).
        if ($request->filled('test_wa')) {
            $number = $validated['wa_central_number'] ?? null;
            $sent = false;

            if ($number) {
                $sent = app(\App\Services\Notifications\WhatsAppGatewayService::class)->send(
                    $number,
                    "Tes notifikasi WhatsApp ALIM.\nWaktu: ".now()->format('d/m/Y H:i:s'),
                    ['event' => 'test', 'reference_id' => 'test-send']
                );
            }

            $detail = app(\App\Services\Notifications\WhatsAppGatewayService::class)->lastResponseDetail();

            return redirect()->back()->with(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Pesan uji terkirim ke '.$number.'. Respons provider: '.($detail ?: 'ok').' (cek Riwayat Pengiriman).'
                    : 'Pesan uji gagal. Respons provider: '.($detail ?: 'tidak diketahui').' — cek provider/token/device.'
            );
        }

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
