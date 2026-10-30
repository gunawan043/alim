<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\UserDevice;
use App\Models\WebPushSubscription;
use App\Services\Notifications\WhatsAppGatewayService;
use App\Services\Recruitment\RecruitmentApiClient;
use App\Services\Sarpras\GoogleDriveService;
use App\Services\SystemMailSettingsService;
use App\Support\ApnsConfig;
use App\Support\FcmConfig;
use App\Support\PosApiConfig;
use App\Support\PosProcurementConfig;
use App\Support\RecruitmentApiConfig;
use App\Support\RecruitmentOutboundConfig;
use App\Support\WebPushConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\IpUtils;

class SystemSettingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSystemSettings();

        $userId = $request->route('userId');
        $tab = $request->tab ?? 'general';

        $settings = SystemSetting::pluck('value', 'key')->toArray();
        $mailSettings = app(SystemMailSettingsService::class)->displayValues();

        $push = [
            'fcm' => [
                'project_id' => FcmConfig::projectId(),
                'configured' => FcmConfig::isConfigured(),
                'has_stored_project_id' => FcmConfig::hasStoredProjectId(),
                'has_stored_service_account' => FcmConfig::hasStoredServiceAccount(),
                'service_account_encrypted' => FcmConfig::storedServiceAccountIsEncrypted(),
                'project_id_from_env' => ! FcmConfig::hasStoredProjectId() && trim((string) config('services.fcm.project_id', '')) !== '',
            ],
            'apns' => [
                'key_id' => ApnsConfig::keyId(),
                'team_id' => ApnsConfig::teamId(),
                'bundle_id' => ApnsConfig::bundleId(),
                'environment' => ApnsConfig::environment(),
                'configured' => ApnsConfig::isConfigured(),
                'has_stored_key' => ApnsConfig::hasStoredPrivateKey(),
                'key_encrypted' => ApnsConfig::storedPrivateKeyIsEncrypted(),
                'key_id_from_env' => trim((string) config('services.apns.key_id', '')) !== '',
            ],
            'device_count' => UserDevice::query()->active()->count(),
            'fcm_device_count' => UserDevice::query()->active()->where('provider', 'fcm')->count(),
            'apns_device_count' => UserDevice::query()->active()->where('provider', 'apns')->count(),
        ];

        $webPush = [
            'configured' => WebPushConfig::isConfigured(),
            'public_key' => WebPushConfig::vapidPublicKey(),
            'subject' => WebPushConfig::subject(),
            'has_public_key' => WebPushConfig::hasStoredPublicKey(),
            'has_private_key' => WebPushConfig::hasStoredPrivateKey(),
            'private_encrypted' => WebPushConfig::storedPrivateKeyIsEncrypted(),
            'public_from_env' => WebPushConfig::publicKeyFromEnv(),
            'subscription_count' => WebPushSubscription::query()->active()->count(),
        ];

        $waLogs = DB::table('wa_message_logs')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()
            ->view('super-admin.system-settings.index', compact('settings', 'tab', 'userId', 'waLogs', 'mailSettings', 'push', 'webPush'))
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function update(Request $request)
    {
        $this->authorizeSystemSettings();

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
            'password_min_length' => 'nullable|integer|min:8|max:64',
            'max_login_attempts' => 'nullable|integer|min:1|max:20',
            'lockout_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'session_lifetime_minutes' => 'nullable|integer|min:1|max:10080',
            'security_2fa_enabled' => 'boolean',
            'security_2fa_mobile_enabled' => 'boolean',
            'security_2fa_scope' => 'nullable|in:all,super_admin',
            'security_admin_ip_allowlist' => 'nullable|string|max:2000',
            'security_notify_login' => 'boolean',
            'security_login_notify_mode' => 'nullable|in:all,new_device',
            'security_notify_password_forgot' => 'boolean',
            'security_notify_password_reset' => 'boolean',
            'security_notify_password_changed' => 'boolean',
            'security_settings' => 'boolean',
            'notification_settings' => 'boolean',
            'mail_settings' => 'boolean',
            'mail_mailer' => 'nullable|in:smtp,log,sendmail,array,failover',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:1000',
            'mail_password_clear' => 'boolean',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'mail_logo_url' => 'nullable|url|max:500',
            'email_notifications' => 'boolean',
            'whatsapp_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'fcm_project_id' => 'nullable|string|max:191',
            'fcm_service_account_json' => 'nullable|string',
            'fcm_service_account_clear' => 'boolean',
            'apns_key_id' => 'nullable|string|max:20',
            'apns_team_id' => 'nullable|string|max:20',
            'apns_bundle_id' => 'nullable|string|max:191',
            'apns_environment' => 'nullable|in:sandbox,production',
            'apns_private_key' => 'nullable|string',
            'apns_private_key_clear' => 'boolean',
            'web_push_vapid_subject' => 'nullable|string|max:500',
            'generate_web_push_keys' => 'boolean',
            'web_push_vapid_clear' => 'boolean',
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
            'wa_brand_footer' => 'nullable|string|max:300',
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
            'pos_procurement_enabled' => 'boolean',
            'pos_procurement_base_url' => 'nullable|url|max:500',
            'pos_procurement_header' => 'nullable|string|max:100|regex:/^[A-Za-z0-9-]+$/',
            'pos_procurement_api_key' => 'nullable|string|max:2000',
            'pos_procurement_api_key_clear' => 'boolean',
            'recruitment_api_header' => 'nullable|string|max:100|regex:/^[A-Za-z0-9-]+$/',
            'recruitment_api_allowed_ips' => 'nullable|string|max:2000',
            'recruitment_outbound_url' => 'nullable|url|max:500',
            'recruitment_outbound_token' => 'nullable|string|max:2000',
            'recruitment_outbound_token_clear' => 'boolean',
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
        // Tab Keamanan: simpan semua toggle, termasuk yang tidak dicentang.
        if ($request->boolean('security_settings')) {
            foreach ([
                'security_2fa_enabled',
                'security_2fa_mobile_enabled',
                'security_notify_login',
                'security_notify_password_forgot',
                'security_notify_password_reset',
                'security_notify_password_changed',
            ] as $securityToggle) {
                $validated[$securityToggle] = $request->boolean($securityToggle) ? '1' : '0';
            }

            $validated['security_2fa_scope'] = in_array($request->input('security_2fa_scope'), ['all', 'super_admin'], true)
                ? $request->input('security_2fa_scope')
                : 'all';

            $validated['security_login_notify_mode'] = in_array($request->input('security_login_notify_mode'), ['all', 'new_device'], true)
                ? $request->input('security_login_notify_mode')
                : 'all';

            [$allowlist, $invalidIps] = $this->normalizeIpAllowlist((string) $request->input('security_admin_ip_allowlist', ''));

            if ($invalidIps !== []) {
                return back()->withInput()->with(
                    'error',
                    'Daftar IP admin tidak valid: '.implode(', ', $invalidIps)
                        .'. Gunakan format IP atau CIDR, pisahkan dengan koma.'
                );
            }

            if ($allowlist !== '' && ! IpUtils::checkIp((string) $request->ip(), explode(',', $allowlist))) {
                return back()->withInput()->with(
                    'error',
                    'IP Anda saat ini tidak termasuk dalam allowlist. Tambahkan '.$request->ip()
                        .' terlebih dahulu agar tidak terkunci dari area admin.'
                );
            }

            $validated['security_admin_ip_allowlist'] = $allowlist;

            $securityDefaults = [
                'otp_expiry_minutes' => 10,
                'password_min_length' => 8,
                'max_login_attempts' => 9,
                'lockout_duration_minutes' => 1440,
                'session_lifetime_minutes' => (int) config('session.lifetime', 120),
            ];

            foreach ($securityDefaults as $key => $default) {
                if (empty($validated[$key])) {
                    $validated[$key] = $default;
                }
            }
        }

        unset($validated['security_settings'], $validated['notification_settings'], $validated['mail_settings']);

        // Tab Email: simpan konfigurasi SMTP. Password hanya ditulis bila diisi.
        if ($request->boolean('mail_settings')) {
            $this->persistMailSettings($request);
        }

        unset($validated['mail_password'], $validated['mail_password_clear']);
        foreach (['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name', 'mail_logo_url'] as $mailKey) {
            unset($validated[$mailKey]);
        }

        if (array_key_exists('wa_central_number', $validated)) {
            $validated['wa_central_number'] = preg_replace('/\D+/', '', (string) $validated['wa_central_number']);
        }

        $notificationTab = $request->boolean('notification_settings');

        // Toggle event: checkbox tidak tercentang = nonaktif.
        if ($notificationTab) {
            // Checkbox tidak tercentang = nonaktif.
            $validated['wa_meta_template_enabled'] = $request->boolean('wa_meta_template_enabled') ? 1 : 0;

            foreach (['wa_event_picked_up', 'wa_event_returned', 'wa_event_sick', 'wa_event_permit_approved', 'wa_event_permit_rejected', 'wa_event_violation', 'wa_event_overdue', 'wa_event_rapor', 'wa_event_holiday', 'wa_event_achievement', 'wa_event_announcement', 'wa_event_visit'] as $toggleKey) {
                $validated[$toggleKey] = $request->boolean($toggleKey) ? 1 : 0;
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
        }
        unset($validated['notif_channels']);

        // Field POS API ditangani khusus di bawah (normalisasi header, IP, dan
        // pembuatan token) — keluarkan dari penyimpanan generik.
        unset($validated['pos_api_header'], $validated['pos_api_allowed_ips']);
        unset(
            $validated['pos_procurement_enabled'],
            $validated['pos_procurement_base_url'],
            $validated['pos_procurement_header'],
            $validated['pos_procurement_api_key'],
            $validated['pos_procurement_api_key_clear']
        );
        unset($validated['recruitment_api_header'], $validated['recruitment_api_allowed_ips']);
        unset(
            $validated['recruitment_outbound_url'],
            $validated['recruitment_outbound_token'],
            $validated['recruitment_outbound_token_clear']
        );

        if ($request->hasAny([
            'google_drive_enabled', 'google_drive_folder_id', 'google_drive_folder_url',
            'google_drive_shared_drive_id', 'google_drive_service_account_json',
        ])) {
            $this->saveGoogleDriveSettings($request);
        }

        foreach (['google_drive_enabled', 'google_drive_folder_id', 'google_drive_folder_url', 'google_drive_shared_drive_id', 'google_drive_service_account_json'] as $driveKey) {
            unset($validated[$driveKey]);
        }

        unset(
            $validated['fcm_project_id'],
            $validated['fcm_service_account_json'],
            $validated['fcm_service_account_clear'],
            $validated['apns_key_id'],
            $validated['apns_team_id'],
            $validated['apns_bundle_id'],
            $validated['apns_environment'],
            $validated['apns_private_key'],
            $validated['apns_private_key_clear'],
            $validated['web_push_vapid_subject'],
            $validated['generate_web_push_keys'],
            $validated['web_push_vapid_clear']
        );

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        if ($request->boolean('security_settings')) {
            $this->writeSettingsAudit($request, 'SECURITY_SETTINGS_UPDATED');
        }

        if ($request->boolean('mail_settings')) {
            $this->writeSettingsAudit($request, 'EMAIL_SETTINGS_UPDATED');
        }

        // ── Tab Push Mobile: FCM + APNs ─────────────────────────────────
        $pushFlash = null;

        if ($request->exists('fcm_project_id')
            || $request->exists('fcm_service_account_json')
            || $request->boolean('fcm_service_account_clear')
            || $request->exists('apns_key_id')
            || $request->exists('apns_team_id')
            || $request->exists('apns_bundle_id')
            || $request->exists('apns_environment')
            || $request->exists('apns_private_key')
            || $request->boolean('apns_private_key_clear')) {
            if ($request->exists('fcm_project_id')) {
                FcmConfig::storeProjectId((string) $request->input('fcm_project_id', ''));
            }

            $serviceAccount = trim((string) $request->input('fcm_service_account_json', ''));

            if ($serviceAccount !== '') {
                $decoded = json_decode($serviceAccount, true);

                if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                    throw ValidationException::withMessages([
                        'fcm_service_account_json' => 'Service account FCM tidak valid. Wajib berisi client_email dan private_key.',
                    ]);
                }

                FcmConfig::storeServiceAccountJson($serviceAccount);
            } elseif ($request->boolean('fcm_service_account_clear')) {
                FcmConfig::clearServiceAccount();
            }

            if ($request->exists('apns_key_id')) {
                ApnsConfig::storeKeyId((string) $request->input('apns_key_id', ''));
            }

            if ($request->exists('apns_team_id')) {
                ApnsConfig::storeTeamId((string) $request->input('apns_team_id', ''));
            }

            if ($request->exists('apns_bundle_id')) {
                ApnsConfig::storeBundleId((string) $request->input('apns_bundle_id', ''));
            }

            if ($request->exists('apns_environment')) {
                ApnsConfig::storeEnvironment((string) $request->input('apns_environment', 'production'));
            }

            $apnsKey = trim((string) $request->input('apns_private_key', ''));

            if ($apnsKey !== '') {
                $normalizedKey = str_replace('\\n', "\n", $apnsKey);

                if (openssl_pkey_get_private($normalizedKey) === false) {
                    throw ValidationException::withMessages([
                        'apns_private_key' => 'Private key APNs tidak valid. Tempel isi file .p8 lengkap.',
                    ]);
                }

                ApnsConfig::storePrivateKey($apnsKey);
            } elseif ($request->boolean('apns_private_key_clear')) {
                ApnsConfig::clearPrivateKey();
            }

            $this->writeSettingsAudit($request, 'PUSH_SETTINGS_UPDATED');
            $pushFlash = 'Pengaturan FCM/APNs berhasil disimpan.';
        }

        $webPushFlash = null;

        if ($request->exists('web_push_vapid_subject')
            || $request->boolean('generate_web_push_keys')
            || $request->boolean('web_push_vapid_clear')) {
            if ($request->exists('web_push_vapid_subject')) {
                $subject = trim((string) $request->input('web_push_vapid_subject', ''));

                if ($subject !== ''
                    && ! str_starts_with($subject, 'mailto:')
                    && filter_var($subject, FILTER_VALIDATE_URL) === false) {
                    throw ValidationException::withMessages([
                        'web_push_vapid_subject' => 'Subject VAPID harus diawali mailto: atau berupa URL.',
                    ]);
                }

                WebPushConfig::storeSubject($subject);
            }

            if ($request->boolean('generate_web_push_keys')) {
                WebPushConfig::generateKeys();
                $this->writeSettingsAudit($request, 'WEB_PUSH_VAPID_KEYS_GENERATED');
                $webPushFlash = 'VAPID key berhasil dibuat. Pengguna perlu mengaktifkan notifikasi browser sekali lagi.';
            } elseif ($request->boolean('web_push_vapid_clear')) {
                WebPushConfig::clearKeys();
                $webPushFlash = 'VAPID key browser berhasil dihapus.';
            } else {
                $webPushFlash = 'Pengaturan Web Push berhasil disimpan.';
            }

            $this->writeSettingsAudit($request, 'WEB_PUSH_SETTINGS_UPDATED');
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

            $currentKey = PosApiConfig::key();
            $rawStoredKey = (string) SystemSetting::get('pos_api_key');
            $regenerated = false;

            if ($request->filled('regenerate_pos_key') || ($request->boolean('pos_api_enabled') && $currentKey === '')) {
                PosApiConfig::storeKey(PosApiConfig::generateKey());
                SystemSetting::set('pos_api_key_generated_at', now()->toDateTimeString(), 'pos_api');
                $regenerated = true;
            } elseif ($rawStoredKey !== '' && ! PosApiConfig::isEncrypted($rawStoredKey) && $currentKey !== '') {
                // Migrasi transparan: token lama yang masih plaintext dienkripsi
                // tanpa mengubah nilainya, sehingga POS tidak perlu diubah.
                PosApiConfig::storeKey($currentKey);
            }

            $this->writeSettingsAudit($request, 'POS_API_SETTINGS_UPDATED');

            if ($regenerated) {
                $this->writeSettingsAudit($request, 'POS_API_TOKEN_ROTATED');
            }

            // Koneksi arah keluar ALIM → POS (API pengadaan).
            PosProcurementConfig::storeEnabled($request->boolean('pos_procurement_enabled'));
            PosProcurementConfig::storeBaseUrl($request->input('pos_procurement_base_url'));
            PosProcurementConfig::storeHeader($request->input('pos_procurement_header'));

            $outboundToken = trim((string) $request->input('pos_procurement_api_key', ''));
            if ($outboundToken !== '') {
                PosProcurementConfig::storeToken($outboundToken);
            } elseif ($request->boolean('pos_procurement_api_key_clear')) {
                PosProcurementConfig::clearToken();
            }

            $posFlash = $regenerated
                ? 'Pengaturan Integrasi POS disimpan dan token baru berhasil dibuat. Salin token ke aplikasi POS sekarang.'
                : 'Pengaturan Integrasi POS berhasil disimpan.';
        }

        // ── Tab Integrasi Recruitment: simpan pengaturan & kelola token ──
        $recruitmentFlash = null;

        if ($request->filled('recruitment_tab')) {
            $headerInput = trim((string) $request->input('recruitment_api_header', ''));
            $allowedIpsInput = trim((string) $request->input('recruitment_api_allowed_ips', ''));

            [$allowedIpsNormalized, $invalidIps] = $this->normalizeIpAllowlist($allowedIpsInput);

            if ($invalidIps !== []) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Daftar IP tidak valid: '.implode(', ', $invalidIps)
                        .'. Gunakan format IP atau CIDR (contoh: 103.10.20.30 atau 10.0.0.0/8), pisahkan dengan koma.'
                );
            }

            SystemSetting::set('recruitment_api_enabled', $request->boolean('recruitment_api_enabled') ? '1' : '0', 'integration');
            SystemSetting::set('recruitment_api_header', $headerInput !== '' ? $headerInput : 'X-ALIM-RECRUITMENT-KEY', 'integration');
            SystemSetting::set('recruitment_api_allowed_ips', $allowedIpsNormalized, 'integration');

            $currentKey = RecruitmentApiConfig::key();
            $rawStoredKey = (string) SystemSetting::get('recruitment_api_key');
            $regenerated = false;

            if ($request->filled('regenerate_recruitment_key') || ($request->boolean('recruitment_api_enabled') && $currentKey === '')) {
                RecruitmentApiConfig::storeKey(RecruitmentApiConfig::generateKey());
                SystemSetting::set('recruitment_api_key_generated_at', now()->toDateTimeString(), 'integration');
                $regenerated = true;
            } elseif ($rawStoredKey !== '' && ! RecruitmentApiConfig::isEncrypted($rawStoredKey) && $currentKey !== '') {
                RecruitmentApiConfig::storeKey($currentKey);
            }

            $this->writeSettingsAudit($request, 'RECRUITMENT_API_SETTINGS_UPDATED');

            if ($regenerated) {
                $this->writeSettingsAudit($request, 'RECRUITMENT_API_TOKEN_ROTATED');
            }

            // Arah keluar: URL + token aplikasi recruitment.
            if ($request->has('recruitment_outbound_url')) {
                RecruitmentOutboundConfig::storeUrl($request->input('recruitment_outbound_url'));
            }

            $outboundToken = trim((string) $request->input('recruitment_outbound_token', ''));

            if ($outboundToken !== '') {
                RecruitmentOutboundConfig::storeToken($outboundToken);
            } elseif ($request->boolean('recruitment_outbound_token_clear')) {
                RecruitmentOutboundConfig::clearToken();
            }

            $this->writeSettingsAudit($request, 'RECRUITMENT_OUTBOUND_SETTINGS_UPDATED');

            $recruitmentFlash = $regenerated
                ? 'Pengaturan Integrasi Recruitment disimpan dan token baru berhasil dibuat. Salin token ke aplikasi recruitment sekarang.'
                : 'Pengaturan Integrasi Recruitment berhasil disimpan.';
        }

        // Clear related caches
        Cache::forget('system_settings');
        Cache::forget('system_settings_map');

        if ($request->filled('test_recruitment_connection')) {
            $result = app(RecruitmentApiClient::class)->testConnection();

            return redirect()->back()->with(
                $result['ok'] ? 'success' : 'error',
                $result['message'].(! empty($result['status']) ? ' (HTTP '.$result['status'].')' : '')
            );
        }

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
            ->with('success', $posFlash ?? $recruitmentFlash ?? $pushFlash ?? $webPushFlash ?? 'Pengaturan berhasil disimpan.');
    }

    /**
     * Halaman pengaturan sistem hanya untuk Super Admin / System Admin.
     *
     * Defense-in-depth: middleware role.access hanya memvalidasi {userId}
     * pada URL, bukan role pengguna. Guard ini melindungi kredensial
     * (WA, email, Google Drive, token POS) dari akses user biasa.
     */
    private function authorizeSystemSettings(): void
    {
        if (! canPermission('super-admin-only')) {
            abort(403, 'Akses hanya untuk Super Admin.');
        }
    }

    private function writeSettingsAudit(Request $request, string $action): void
    {
        try {
            AuditLog::create([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'table_name' => 'system_settings',
                'record_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal menulis audit perubahan pengaturan', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function testEmail(Request $request)
    {
        $this->authorizeSystemSettings();

        $user = $request->user();

        if (! $user?->email) {
            return redirect()->back()->with('error', 'Akun Anda belum memiliki alamat email.');
        }

        $validated = $request->validate([
            'test_email_to' => 'nullable|email|max:255',
        ]);

        if ($request->boolean('mail_settings')) {
            $this->persistMailSettings($request, validate: true);
        }

        $target = $validated['test_email_to'] ?? $user->email;
        app(SystemMailSettingsService::class)->apply();

        try {
            Mail::raw(
                "Tes notifikasi email ALIM.\n\nWaktu: ".now()->format('d/m/Y H:i:s')
                    ."\nPenerima: {$target}\n\nJika email ini diterima, konfigurasi MAIL_* sudah benar.",
                function ($message) use ($target) {
                    $message->to($target)->subject('Tes Notifikasi Email ALIM');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email tes', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'error',
                'Gagal mengirim email tes: '.$e->getMessage().' Periksa MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, dan MAIL_PASSWORD.'
            );
        }

        if (config('mail.default') === 'log') {
            return redirect()->back()->with(
                'success',
                'Email tes untuk '.$target.' ditulis ke log (MAIL_MAILER=log). Periksa storage/logs/laravel.log.'
            );
        }

        return redirect()->back()->with(
            'success',
            'Email tes dikirim ke '.$target.'. Periksa inbox/spam. Jika tidak masuk, periksa konfigurasi MAIL_*.'
        );
    }

    public function testGoogleDrive(Request $request)
    {
        $this->authorizeSystemSettings();

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
    private function persistMailSettings(Request $request, bool $validate = false): void
    {
        if ($validate) {
            $request->validate([
                'mail_mailer' => 'nullable|in:smtp,log,sendmail,array,failover',
                'mail_host' => 'nullable|string|max:255',
                'mail_port' => 'nullable|integer|min:1|max:65535',
                'mail_username' => 'nullable|string|max:255',
                'mail_password' => 'nullable|string|max:1000',
                'mail_password_clear' => 'boolean',
                'mail_encryption' => 'nullable|in:tls,ssl,none',
                'mail_from_address' => 'nullable|email|max:255',
                'mail_from_name' => 'nullable|string|max:255',
                'mail_logo_url' => 'nullable|url|max:500',
            ]);
        }

        foreach ([
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_encryption', 'mail_from_address', 'mail_from_name', 'mail_logo_url',
        ] as $mailKey) {
            if ($request->has($mailKey)) {
                SystemSetting::updateOrCreate(
                    ['key' => $mailKey],
                    ['value' => $request->input($mailKey), 'group' => 'mail']
                );
            }
        }

        $mailPassword = trim((string) $request->input('mail_password', ''));

        if ($mailPassword !== '') {
            SystemSetting::updateOrCreate(
                ['key' => 'mail_password'],
                ['value' => Crypt::encryptString($mailPassword), 'group' => 'mail']
            );
        } elseif ($request->boolean('mail_password_clear')) {
            SystemSetting::where('key', 'mail_password')->delete();
        }

        Cache::forget('system_settings_map');
        app(SystemMailSettingsService::class)->apply();
    }

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
        $this->authorizeSystemSettings();

        Cache::flush();

        return redirect()->back()
            ->with('success', 'Cache berhasil dibersihkan.');
    }
}
