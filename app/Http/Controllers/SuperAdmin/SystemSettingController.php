<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Notifications\WhatsAppGatewayService;
use App\Services\Sarpras\GoogleDriveService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SystemSettingController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $tab = $request->tab ?? 'general';

        $settings = SystemSetting::pluck('value', 'key')->toArray();

        $waLogs = DB::table('wa_message_logs')
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
            'wa_meta_phone_number_id' => 'nullable|string|max:100',
            'wa_meta_access_token' => 'nullable|string|max:2000',
            'wa_meta_api_version' => 'nullable|string|max:10',
            'wa_meta_template_name' => 'nullable|string|max:100',
            'wa_meta_template_language' => 'nullable|string|max:10',
            'wa_meta_template_enabled' => 'boolean',
            'wa_meta_verify_token' => 'nullable|string|max:100',
            'wa_meta_app_secret' => 'nullable|string|max:200',
            'notif_channels' => 'nullable|array',
            'notif_channels.*' => 'array',
            'notif_channels.*.*' => 'boolean',
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
            'pos_api_header' => 'nullable|string|max:100|regex:/^[A-Za-z0-9-]+$/',
            'pos_api_allowed_ips' => 'nullable|string|max:2000',
            'google_drive_enabled' => 'boolean',
            'google_drive_folder_id' => 'nullable|string|max:191',
            'google_drive_folder_url' => 'nullable|url|max:500',
            'google_drive_shared_drive_id' => 'nullable|string|max:191',
            'google_drive_service_account_json' => 'nullable|string',
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
        if (array_key_exists('wa_meta_access_token', $validated)) {
            $validated['wa_meta_access_token'] = trim((string) $validated['wa_meta_access_token']);
        }
        if (array_key_exists('wa_meta_phone_number_id', $validated)) {
            $validated['wa_meta_phone_number_id'] = preg_replace('/\D+/', '', (string) $validated['wa_meta_phone_number_id']);
        }
        if (array_key_exists('wa_meta_api_version', $validated)) {
            $validated['wa_meta_api_version'] = trim((string) $validated['wa_meta_api_version']) ?: 'v20.0';
        }
        // Checkbox tidak tercentang = nonaktif.
        $validated['wa_meta_template_enabled'] = $request->boolean('wa_meta_template_enabled') ? 1 : 0;
        if (array_key_exists('wa_central_number', $validated)) {
            $validated['wa_central_number'] = preg_replace('/\D+/', '', (string) $validated['wa_central_number']);
        }

        // Toggle event: checkbox tidak tercentang = nonaktif.
        foreach (['wa_event_picked_up', 'wa_event_returned', 'wa_event_sick', 'wa_event_permit_approved', 'wa_event_permit_rejected', 'wa_event_violation', 'wa_event_overdue', 'wa_event_rapor', 'wa_event_holiday', 'wa_event_achievement', 'wa_event_announcement', 'wa_event_visit'] as $toggleKey) {
            if (! $request->filled('test_wa') || true) {
                $validated[$toggleKey] = $request->boolean($toggleKey) ? 1 : 0;
            }
        }

        // Matriks channel notifikasi: simpan sebagai JSON.
        $notifMatrix = [];
        foreach (array_keys(config('notifications.events', [])) as $event) {
            foreach (array_keys(config('notifications.channels', [])) as $channel) {
                $notifMatrix[$event][$channel] = $request->boolean("notif_channels.{$event}.{$channel}");
            }
        }
        SystemSetting::updateOrCreate(
            ['key' => 'notification_channel_matrix'],
            ['value' => json_encode($notifMatrix), 'group' => 'notifications']
        );
        unset($validated['notif_channels']);

        // Field POS API ditangani khusus di bawah (normalisasi header, IP, dan
        // pembuatan token) — keluarkan dari penyimpanan generik.
        unset($validated['pos_api_header'], $validated['pos_api_allowed_ips']);

        if ($request->hasAny([
            'google_drive_enabled', 'google_drive_folder_id', 'google_drive_folder_url',
            'google_drive_shared_drive_id', 'google_drive_service_account_json',
        ])) {
            $this->saveGoogleDriveSettings($request);
        }

        foreach (['google_drive_enabled', 'google_drive_folder_id', 'google_drive_folder_url', 'google_drive_shared_drive_id', 'google_drive_service_account_json'] as $driveKey) {
            unset($validated[$driveKey]);
        }

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // ── Tab Integrasi POS: simpan pengaturan & kelola token ──────────
        $posFlash = null;

        if ($request->filled('pos_tab')) {
            $headerInput = trim((string) $request->input('pos_api_header', ''));
            $allowedIpsInput = trim((string) $request->input('pos_api_allowed_ips', ''));

            [$allowedIpsNormalized, $invalidIps] = $this->normalizeIpAllowlist($allowedIpsInput);

            if ($invalidIps !== []) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Daftar IP tidak valid: '.implode(', ', $invalidIps)
                        .'. Gunakan format IP atau CIDR (contoh: 103.10.20.30 atau 10.0.0.0/8), pisahkan dengan koma.'
                );
            }

            SystemSetting::set('pos_api_enabled', $request->boolean('pos_api_enabled') ? '1' : '0', 'pos_api');
            SystemSetting::set('pos_api_header', $headerInput !== '' ? $headerInput : 'X-ALIM-POS-KEY', 'pos_api');
            SystemSetting::set('pos_api_allowed_ips', $allowedIpsNormalized, 'pos_api');

            $currentKey = (string) (SystemSetting::get('pos_api_key') ?: '');
            $regenerated = false;

            if ($request->filled('regenerate_pos_key') || ($request->boolean('pos_api_enabled') && $currentKey === '')) {
                SystemSetting::set('pos_api_key', bin2hex(random_bytes(32)), 'pos_api');
                SystemSetting::set('pos_api_key_generated_at', now()->toDateTimeString(), 'pos_api');
                $regenerated = true;
            }

            $posFlash = $regenerated
                ? 'Pengaturan Integrasi POS disimpan dan token baru berhasil dibuat. Salin token ke aplikasi POS sekarang.'
                : 'Pengaturan Integrasi POS berhasil disimpan.';
        }

        // Clear related caches
        Cache::forget('system_settings');
        Cache::forget('system_settings_map');

        // Cek status device provider (Fonnte) — diagnosa cepat.
        if ($request->filled('check_device')) {
            $info = app(WhatsAppGatewayService::class)->checkDevice();

            return redirect()->back()->with(
                ! empty($info['ok']) ? 'success' : 'error',
                ! empty($info['ok'])
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
                $sent = app(WhatsAppGatewayService::class)->send(
                    $number,
                    "Tes notifikasi WhatsApp ALIM.\nWaktu: ".now()->format('d/m/Y H:i:s'),
                    ['event' => 'test', 'reference_id' => 'test-send']
                );
            }

            $detail = app(WhatsAppGatewayService::class)->lastResponseDetail();

            return redirect()->back()->with(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Pesan uji terkirim ke '.$number.'. Respons provider: '.($detail ?: 'ok').' (cek Riwayat Pengiriman).'
                    : 'Pesan uji gagal. Respons provider: '.($detail ?: 'tidak diketahui').' — cek provider/token/device.'
            );
        }

        return redirect()->back()
            ->with('success', $posFlash ?? 'Pengaturan berhasil disimpan.');
    }

    public function testGoogleDrive(Request $request)
    {
        // Simpan dulu nilai dari form, karena tombol Test ada di form pengaturan.
        try {
            $this->saveGoogleDriveSettings($request);
        } catch (ValidationException $e) {
            throw $e;
        }

        $result = app(GoogleDriveService::class)->testConnection();

        return redirect()->back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function saveGoogleDriveSettings(Request $request): void
    {
        $validated = $request->validate([
            'google_drive_enabled' => 'boolean',
            'google_drive_folder_id' => 'nullable|string|max:191',
            'google_drive_folder_url' => 'nullable|url|max:500',
            'google_drive_shared_drive_id' => 'nullable|string|max:191',
            'google_drive_service_account_json' => 'nullable|string',
        ]);

        $enabled = $request->boolean('google_drive_enabled');
        $folderId = trim((string) ($validated['google_drive_folder_id'] ?? ''));

        if ($enabled && $folderId === '') {
            throw ValidationException::withMessages([
                'google_drive_folder_id' => 'ID folder Google Drive wajib diisi saat Drive diaktifkan.',
            ]);
        }

        SystemSetting::set('google_drive_enabled', $enabled ? '1' : '0', 'google_drive');
        SystemSetting::set('google_drive_folder_id', $folderId ?: null, 'google_drive');
        SystemSetting::set('google_drive_folder_url', $validated['google_drive_folder_url'] ?? null, 'google_drive');
        SystemSetting::set('google_drive_shared_drive_id', $validated['google_drive_shared_drive_id'] ?? null, 'google_drive');

        $driveJson = trim((string) ($validated['google_drive_service_account_json'] ?? ''));

        if ($driveJson !== '') {
            $decoded = json_decode($driveJson, true);
            if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                throw ValidationException::withMessages([
                    'google_drive_service_account_json' => 'JSON service account tidak valid. Wajib berisi client_email dan private_key.',
                ]);
            }

            SystemSetting::updateOrCreate(
                ['key' => 'google_drive_service_account_json'],
                ['value' => Crypt::encryptString(json_encode($decoded)), 'group' => 'google_drive']
            );
            SystemSetting::set('google_drive_service_account_email', $decoded['client_email'], 'google_drive');
            SystemSetting::set('google_drive_project_id', $decoded['project_id'] ?? null, 'google_drive');
        }
    }

    /**
     * Normalisasi daftar IP/CIDR (dipisah koma) untuk whitelist POS.
     *
     * @return array{0: string, 1: list<string>} [nilai ternormalisasi, item tidak valid]
     */
    private function normalizeIpAllowlist(string $input): array
    {
        $items = array_values(array_filter(
            array_map('trim', explode(',', $input)),
            fn (string $item) => $item !== ''
        ));

        $valid = [];
        $invalid = [];

        foreach ($items as $item) {
            if ($this->isValidIpOrCidr($item)) {
                $valid[] = $item;
            } else {
                $invalid[] = $item;
            }
        }

        return [implode(',', $valid), $invalid];
    }

    /**
     * Cek apakah sebuah nilai berupa IP atau blok CIDR (IPv4/IPv6).
     */
    private function isValidIpOrCidr(string $value): bool
    {
        if (! str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) !== false;
        }

        [$ip, $prefix] = explode('/', $value, 2);

        if (! is_numeric($prefix)) {
            return false;
        }

        $prefix = (int) $prefix;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $prefix >= 0 && $prefix <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $prefix >= 0 && $prefix <= 128;
        }

        return false;
    }

    public function clearCache()
    {
        Cache::flush();

        return redirect()->back()
            ->with('success', 'Cache berhasil dibersihkan.');
    }
}
