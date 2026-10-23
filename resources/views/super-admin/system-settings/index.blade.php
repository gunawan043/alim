@extends('layouts.master')
@section('title') Pengaturan Sistem @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') Pengaturan Sistem @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'general' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'general']) }}">
                                <i class="ri-settings-3-line me-1"></i> Umum
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'email' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'email']) }}">
                                <i class="ri-mail-settings-line me-1"></i> Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'security' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'security']) }}">
                                <i class="ri-shield-keyhole-line me-1"></i> Keamanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'notifications' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'notifications']) }}">
                                <i class="ri-notification-3-line me-1"></i> Notifikasi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'pos-api' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'pos-api']) }}">
                                <i class="ri-plug-line me-1"></i> Integrasi POS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'recruitment-api' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'recruitment-api']) }}">
                                <i class="ri-user-search-line me-1"></i> Integrasi Recruitment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'google-drive' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'google-drive']) }}">
                                <i class="ri-google-line me-1"></i> Google Drive
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'cache' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'cache']) }}">
                                <i class="ri-database-2-line me-1"></i> Cache & Utils
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.system-settings.update', ['userId' => $userId]) }}">
                        @csrf

                        {{-- TAB: General --}}
                        @if($tab === 'general')
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Aplikasi</label>
                                    <input type="text" name="app_name" class="form-control"
                                        value="{{ $settings['app_name'] ?? '' }}" placeholder="Alim">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tagline</label>
                                    <input type="text" name="app_tagline" class="form-control"
                                        value="{{ $settings['app_tagline'] ?? '' }}" placeholder="Sistem Manajemen GTK">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">URL Aplikasi</label>
                                    <input type="url" name="app_url" class="form-control"
                                        value="{{ $settings['app_url'] ?? '' }}" placeholder="https://Alim.example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email resmi</label>
                                    <input type="email" name="app_email" class="form-control"
                                        value="{{ $settings['app_email'] ?? '' }}" placeholder="admin@Alim.ac.id">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="app_phone" class="form-control"
                                        value="{{ $settings['app_phone'] ?? '' }}" placeholder="+62xxx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Alamat</label>
                                    <input type="text" name="app_address" class="form-control"
                                        value="{{ $settings['app_address'] ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Timezone</label>
                                    <select name="timezone" class="form-control">
                                        <option value="Asia/Jakarta" {{ ($settings['timezone'] ?? '') === 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                                        <option value="Asia/Makassar" {{ ($settings['timezone'] ?? '') === 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                                        <option value="Asia/Jayapura" {{ ($settings['timezone'] ?? '') === 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Format Tanggal</label>
                                    <select name="date_format" class="form-control">
                                        <option value="d/m/Y" {{ ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' }}>dd/mm/yyyy</option>
                                        <option value="Y-m-d" {{ ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' }}>yyyy-mm-dd</option>
                                        <option value="m/d/Y" {{ ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' }}>mm/dd/yyyy</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pagination Default</label>
                                    <input type="number" name="pagination_default" class="form-control"
                                        value="{{ $settings['pagination_default'] ?? 20 }}" min="5" max="100">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="registration_enabled" value="1"
                                            id="reg-enabled" {{ ($settings['registration_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="reg-enabled">Registrasi user dibolehkan</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1"
                                            id="maint-mode" {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="maint-mode">Mode Maintenance</label>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Email --}}
                        @if($tab === 'email')
                            <input type="hidden" name="mail_settings" value="1">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="card border h-100">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <h6 class="mb-0"><i class="ri-mail-settings-line me-1"></i>Konfigurasi Email Sistem</h6>
                                                <small class="text-muted">Dipakai untuk OTP, reset password, dan notifikasi email. Perubahan pada form ini ikut disimpan saat Test Email.</small>
                                            </div>
                                            <button type="submit"
                                                formaction="{{ route('user.sa.system-settings.test-email', ['userId' => $userId]) }}"
                                                class="btn btn-sm btn-soft-info flex-shrink-0">
                                                <i class="ri-mail-send-line me-1"></i>Test Email
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            @if(($mailSettings['mail_mailer'] ?? '') === 'smtp' && in_array($mailSettings['mail_host'] ?? '', ['', 'smtp.example.com'], true))
                                                <div class="alert alert-danger small">
                                                    <i class="ri-error-warning-line me-1"></i>
                                                    <strong>Email belum siap.</strong>
                                                    Isi host SMTP dan kredensial di bawah, lalu klik <strong>Test Email</strong>.
                                                </div>
                                            @elseif(($mailSettings['mail_mailer'] ?? '') === 'log')
                                                <div class="alert alert-info small">
                                                    <i class="ri-information-line me-1"></i>
                                                    Mode <strong>log</strong> aktif. Email tidak dikirim ke inbox, tetapi dicatat di
                                                    <code>storage/logs/laravel.log</code>.
                                                </div>
                                            @endif

                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Mailer</label>
                                                    <select name="mail_mailer" class="form-select">
                                                        @foreach(['smtp' => 'SMTP', 'log' => 'Log (development)', 'sendmail' => 'Sendmail', 'failover' => 'Failover'] as $value => $label)
                                                            <option value="{{ $value }}" {{ ($mailSettings['mail_mailer'] ?? 'smtp') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Host SMTP</label>
                                                    <input type="text" name="mail_host" class="form-control"
                                                        value="{{ $mailSettings['mail_host'] ?? '' }}" placeholder="mail.domain.com">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Port</label>
                                                    <input type="number" name="mail_port" class="form-control"
                                                        value="{{ $mailSettings['mail_port'] ?? 587 }}" min="1" max="65535">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Enkripsi</label>
                                                    <select name="mail_encryption" class="form-select">
                                                        @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Tanpa Enkripsi'] as $value => $label)
                                                            <option value="{{ $value }}" {{ ($mailSettings['mail_encryption'] ?? 'tls') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Username SMTP</label>
                                                    <input type="text" name="mail_username" class="form-control"
                                                        value="{{ $mailSettings['mail_username'] ?? '' }}" autocomplete="off"
                                                        placeholder="biasanya alamat email lengkap">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">
                                                        Password SMTP
                                                        @if($mailSettings['mail_password_set'] ?? false)
                                                            <span class="badge bg-success-subtle text-success ms-1">Tersimpan</span>
                                                        @endif
                                                    </label>
                                                    <input type="password" name="mail_password" class="form-control"
                                                        value="" autocomplete="new-password"
                                                        placeholder="{{ ($mailSettings['mail_password_set'] ?? false) ? 'Kosongkan jika tidak diubah' : 'Masukkan password SMTP' }}">
                                                    <div class="form-text">Password disimpan terenkripsi. Kosongkan bila tidak ingin mengubah.</div>
                                                    @if($mailSettings['mail_password_set'] ?? false)
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox" name="mail_password_clear" value="1" id="mail-password-clear">
                                                            <label class="form-check-label small" for="mail-password-clear">Hapus password SMTP tersimpan</label>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Email Pengirim</label>
                                                    <input type="email" name="mail_from_address" class="form-control"
                                                        value="{{ $mailSettings['mail_from_address'] ?? '' }}" placeholder="noreply@domain.com">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Nama Pengirim</label>
                                                    <input type="text" name="mail_from_name" class="form-control"
                                                        value="{{ $mailSettings['mail_from_name'] ?? '' }}" placeholder="ALIM">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">URL Logo Email</label>
                                                    <input type="url" name="mail_logo_url" class="form-control"
                                                        value="{{ $mailSettings['mail_logo_url'] ?: 'https://cdn.jsdelivr.net/gh/gunawan043/alim@main/public/build/images/alim-light-name.png' }}"
                                                        placeholder="https://cdn.jsdelivr.net/gh/.../alim-light-name.png">
                                                    <div class="form-text">
                                                        Logo harus berupa URL publik yang dapat diakses penerima email.
                                                        Default memakai logo ALIM dari repository GitHub.
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Email Tujuan Uji Coba</label>
                                                    <input type="email" name="test_email_to" class="form-control"
                                                        value="{{ old('test_email_to', auth()->user()->email ?? '') }}"
                                                        placeholder="email@contoh.com">
                                                    <div class="form-text">Khusus tombol Test Email. Kosongkan untuk memakai email akun yang sedang login.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card border h-100">
                                        <div class="card-header"><h6 class="mb-0">Status Konfigurasi</h6></div>
                                        <div class="card-body small">
                                            <div class="mb-2 d-flex justify-content-between">
                                                <span class="text-muted">Mailer</span>
                                                <strong>{{ strtoupper($mailSettings['mail_mailer'] ?? 'smtp') }}</strong>
                                            </div>
                                            <div class="mb-2 d-flex justify-content-between">
                                                <span class="text-muted">Host</span>
                                                <strong>{{ $mailSettings['mail_host'] ?: '(dari .env)' }}</strong>
                                            </div>
                                            <div class="mb-2 d-flex justify-content-between">
                                                <span class="text-muted">Port</span>
                                                <strong>{{ $mailSettings['mail_port'] ?: '(default)' }}</strong>
                                            </div>
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="text-muted">Password</span>
                                                @if($mailSettings['mail_password_set'] ?? false)
                                                    <span class="badge bg-success-subtle text-success">Tersimpan</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Belum diisi</span>
                                                @endif
                                            </div>
                                            <div class="alert alert-info mb-0">
                                                <i class="ri-shield-lock-line me-1"></i>
                                                Nilai kosong akan memakai konfigurasi <code>.env</code>.
                                                Gunakan <code>MAIL_MAILER=log</code> untuk development tanpa SMTP.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Security --}}
                        @if($tab === 'security')
                            <input type="hidden" name="security_settings" value="1">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label">OTP Expiry (menit)</label>
                                    <input type="number" name="otp_expiry_minutes" class="form-control"
                                        value="{{ $settings['otp_expiry_minutes'] ?? 10 }}" min="1" max="60">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Password Minimal</label>
                                    <input type="number" name="password_min_length" class="form-control"
                                        value="{{ $settings['password_min_length'] ?? 8 }}" min="8" max="64">
                                    <small class="text-muted">Minimal 8 karakter.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" class="form-control"
                                        value="{{ $settings['max_login_attempts'] ?? 9 }}" min="1" max="20">
                                    <small class="text-muted">Berlaku untuk akun dan pembatasan IP.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Lockout Duration (menit)</label>
                                    <input type="number" name="lockout_duration_minutes" class="form-control"
                                        value="{{ $settings['lockout_duration_minutes'] ?? 1440 }}" min="1" max="1440">
                                    <small class="text-muted">Default 1440 menit (24 jam), sama dengan perilaku sebelumnya.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Session Lifetime (menit)</label>
                                    <input type="number" name="session_lifetime_minutes" class="form-control"
                                        value="{{ $settings['session_lifetime_minutes'] ?? config('session.lifetime', 120) }}" min="1" max="10080">
                                    <small class="text-muted">Diterapkan sebelum session dimulai. Max: 10080 menit (7 hari).</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">IP Allowlist Area Admin</label>
                                    <textarea name="security_admin_ip_allowlist" class="form-control" rows="2"
                                        placeholder="Contoh: 103.10.20.30, 10.0.0.0/8">{{ $settings['security_admin_ip_allowlist'] ?? '' }}</textarea>
                                    <small class="text-muted">Kosong = semua IP diizinkan. Pisahkan dengan koma. IP Anda saat ini: <code>{{ request()->ip() }}</code></small>
                                </div>
                            </div>

                            <div class="row g-4 mt-1">
                                <div class="col-lg-6">
                                    <div class="card h-100 border">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <h6 class="mb-0"><i class="ri-shield-check-line me-1"></i>Notifikasi Keamanan Akun</h6>
                                                <small class="text-muted">Kirim peringatan ke email, WhatsApp, dan notifikasi sistem.</small>
                                            </div>
                                            <button type="submit"
                                                formaction="{{ route('user.sa.system-settings.test-email', ['userId' => $userId]) }}"
                                                class="btn btn-sm btn-soft-info flex-shrink-0">
                                                <i class="ri-mail-send-line me-1"></i>Test Email
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            @if(($mailSettings['mail_mailer'] ?? '') === 'smtp' && in_array($mailSettings['mail_host'] ?? '', ['', 'smtp.example.com'], true))
                                                <div class="alert alert-danger small">
                                                    <i class="ri-error-warning-line me-1"></i>
                                                    <strong>Email belum dikonfigurasi.</strong>
                                                    Host SMTP masih placeholder. OTP 2FA dan email notifikasi tidak akan terkirim
                                                    sampai konfigurasi Email Sistem diisi.
                                                </div>
                                            @elseif(($mailSettings['mail_mailer'] ?? '') === 'log')
                                                <div class="alert alert-info small">
                                                    <i class="ri-information-line me-1"></i>
                                                    Mode email saat ini <strong>log</strong>. Email tidak benar-benar dikirim;
                                                    isinya tercatat di <code>storage/logs/laravel.log</code>.
                                                </div>
                                            @endif
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="security_notify_login" value="1"
                                                    id="security-notify-login" {{ ($settings['security_notify_login'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security-notify-login">Login berhasil</label>
                                                <div class="form-text">Email + WhatsApp + notifikasi sistem setiap login berhasil.</div>
                                            </div>
                                            <div class="ms-4 mb-3">
                                                <label class="form-label small mb-1">Mode notifikasi login</label>
                                                <select name="security_login_notify_mode" class="form-select form-select-sm">
                                                    <option value="all" {{ ($settings['security_login_notify_mode'] ?? 'all') === 'all' ? 'selected' : '' }}>Setiap login</option>
                                                    <option value="new_device" {{ ($settings['security_login_notify_mode'] ?? 'all') === 'new_device' ? 'selected' : '' }}>Hanya perangkat/IP baru</option>
                                                </select>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="security_notify_password_forgot" value="1"
                                                    id="security-notify-forgot" {{ ($settings['security_notify_password_forgot'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security-notify-forgot">Permintaan lupa password</label>
                                                <div class="form-text">Email OTP tetap dikirim; WhatsApp + notifikasi sistem mencatat permintaan.</div>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="security_notify_password_reset" value="1"
                                                    id="security-notify-reset" {{ ($settings['security_notify_password_reset'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security-notify-reset">Password berhasil direset</label>
                                                <div class="form-text">Email pemberitahuan + WhatsApp + notifikasi sistem.</div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="security_notify_password_changed" value="1"
                                                    id="security-notify-changed" {{ ($settings['security_notify_password_changed'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security-notify-changed">Password diubah dari profil</label>
                                                <div class="form-text">Email pemberitahuan + WhatsApp + notifikasi sistem.</div>
                                            </div>
                                            <div class="alert alert-info small mb-0 mt-3">
                                                <i class="ri-links-line me-1"></i>
                                                Jika notifikasi tidak muncul, periksa juga
                                                <a href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'notifications']) }}">Matriks Channel Notifikasi</a>.
                                                Channel yang dimatikan di matriks tidak akan mengirim pesan.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card h-100 border">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-lock-password-line me-1"></i>Two-Factor Authentication (2FA)</h6>
                                            @if(($settings['security_2fa_enabled'] ?? '0') == '1')
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                            @endif
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="security_2fa_enabled" value="1"
                                                    id="security-2fa-enabled" {{ ($settings['security_2fa_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="security-2fa-enabled">Aktifkan 2FA saat login</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="security_2fa_mobile_enabled" value="1"
                                                    id="security-2fa-mobile" {{ ($settings['security_2fa_mobile_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security-2fa-mobile">Terapkan 2FA pada login aplikasi mobile</label>
                                                <div class="form-text">Default nonaktif. Aktifkan hanya setelah aplikasi mobile mendukung challenge 2FA.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Berlaku untuk</label>
                                                <select name="security_2fa_scope" class="form-select form-select-sm">
                                                    <option value="all" {{ ($settings['security_2fa_scope'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua pengguna web</option>
                                                    <option value="super_admin" {{ ($settings['security_2fa_scope'] ?? 'all') === 'super_admin' ? 'selected' : '' }}>Super Admin / System Admin saja</option>
                                                </select>
                                                <div class="form-text">Disarankan Super Admin saja jika 2FA baru diuji.</div>
                                            </div>
                                            <div class="alert alert-warning small mb-0">
                                                <i class="ri-information-line me-1"></i>
                                                Default <strong>nonaktif</strong> agar development tidak terganggu.
                                                Jika diaktifkan, pengguna yang login melalui web akan diminta kode OTP email setelah password benar.
                                                Kode berlaku sesuai <strong>OTP Expiry</strong> di atas. Perubahan berlaku pada login berikutnya.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Notifications --}}
                        @if($tab === 'notifications')
                            <input type="hidden" name="notification_settings" value="1">
                            <div class="row g-4">
                                <div class="col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-notification-3-line me-1"></i>Notifikasi Umum</h6></div>
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="email_notifications" value="1"
                                                    id="email-notif" {{ ($settings['email_notifications'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="email-notif">Notifikasi Email</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="whatsapp_notifications" value="1"
                                                    id="wa-notif" {{ ($settings['whatsapp_notifications'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="wa-notif">Notifikasi WhatsApp</label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="push_notifications" value="1"
                                                    id="push-notif" {{ ($settings['push_notifications'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="push-notif">Push Notifications</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class="card h-100">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-whatsapp-line me-1"></i>Gateway WhatsApp (Fonnte / Meta Cloud API Resmi)</h6>
                                            <div class="d-flex gap-2">
                                                <button type="submit" name="check_device" value="1" class="btn btn-sm btn-soft-info">
                                                    <i class="ri-signal-tower-line me-1"></i>Cek Status Device
                                                </button>
                                                <button type="submit" name="test_wa" value="1" class="btn btn-sm btn-soft-success">
                                                    <i class="ri-send-plane-line me-1"></i>Uji Kirim ke Nomor Pusat
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Status Gateway</label>
                                                    <select name="wa_enabled" class="form-select form-select-sm">
                                                        <option value="1" {{ ($settings['wa_enabled'] ?? '1') == '1' ? 'selected' : '' }}>Aktif</option>
                                                        <option value="0" {{ ($settings['wa_enabled'] ?? '1') == '0' ? 'selected' : '' }}>Nonaktif</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Provider</label>
                                                    <select name="wa_provider" class="form-select form-select-sm">
                                                        @foreach(['log' => 'Log / Gratis (wa.me manual)', 'fonnte' => 'Fonnte (tidak resmi)', 'meta' => 'Meta WhatsApp Cloud API (resmi)', 'wablas' => 'Wablas', 'generic' => 'Generic Webhook'] as $value => $label)
                                                            <option value="{{ $value }}" {{ ($settings['wa_provider'] ?? 'log') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Mode Penerima</label>
                                                    <select name="wa_delivery_mode" class="form-select form-select-sm">
                                                        <option value="central" {{ ($settings['wa_delivery_mode'] ?? 'central') === 'central' ? 'selected' : '' }}>Terpusat — nomor admin ALIM</option>
                                                        <option value="guardian" {{ ($settings['wa_delivery_mode'] ?? '') === 'guardian' ? 'selected' : '' }}>Ke wali santri</option>
                                                        <option value="both" {{ ($settings['wa_delivery_mode'] ?? '') === 'both' ? 'selected' : '' }}>Keduanya</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Nomor Admin ALIM (pusat)</label>
                                                    <input type="text" name="wa_central_number" class="form-control form-control-sm" value="{{ $settings['wa_central_number'] ?? '' }}" placeholder="08xxxxxxxxxx">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Footer Branding ALIM (opsional)</label>
                                                    <input type="text" name="wa_brand_footer" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_brand_footer'] ?? '' }}"
                                                           placeholder="ALIM — https://alim.sch.id">
                                                    <div class="form-text">Ditambahkan di akhir pesan. Watermark Fonnte tetap mengikuti paket device.</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">API Token</label>
                                                    <input type="text" name="wa_api_token" class="form-control form-control-sm" value="{{ $settings['wa_api_token'] ?? '' }}" placeholder="token Fonnte/Wablas">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">API URL (opsional)</label>
                                                    <input type="text" name="wa_api_url" class="form-control form-control-sm" value="{{ $settings['wa_api_url'] ?? '' }}" placeholder="https://api.fonnte.com/send">
                                                </div>
                                                <input type="hidden" name="wa_sender" value="{{ $settings['wa_sender'] ?? '' }}">

                                                <div class="col-12"><hr class="my-1"></div>
                                                <div class="col-12">
                                                    <div class="alert alert-info small mb-0">
                                                        <strong><i class="ri-verified-badge-line me-1"></i>Meta WhatsApp Cloud API (resmi)</strong> —
                                                        buat aplikasi di <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com/apps</a>
                                                        → WhatsApp → API Setup. Salin <em>Phone Number ID</em> dan <em>permanent access token</em>.
                                                        <br>
                                                        Webhook URL: <code>{{ url('/webhook/whatsapp') }}</code> ·
                                                        Verify token: <code>{{ $settings['wa_meta_verify_token'] ?? '(isi di bawah)' }}</code>
                                                        <br>
                                                        <span class="text-muted">Nomor uji Meta gratis untuk pengembangan. Untuk produksi, pengiriman di luar jendela 24 jam wajib memakai template yang disetujui Meta (biaya per pesan sesuai tarif Meta).</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Meta Phone Number ID</label>
                                                    <input type="text" name="wa_meta_phone_number_id" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_phone_number_id'] ?? '' }}" placeholder="123456789012345">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Meta Access Token (permanent)</label>
                                                    <input type="password" name="wa_meta_access_token" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_access_token'] ?? '' }}" placeholder="EAAG...">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">API Version</label>
                                                    <input type="text" name="wa_meta_api_version" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_api_version'] ?? 'v20.0' }}" placeholder="v20.0">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Webhook Verify Token</label>
                                                    <input type="text" name="wa_meta_verify_token" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_verify_token'] ?? '' }}" placeholder="token rahasia">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">App Secret (opsional)</label>
                                                    <input type="password" name="wa_meta_app_secret" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_app_secret'] ?? '' }}" placeholder="untuk validasi signature">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" name="wa_meta_template_enabled" value="1"
                                                               id="waMetaTemplateEnabled" {{ ($settings['wa_meta_template_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="waMetaTemplateEnabled">Gunakan template Meta</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Nama Template</label>
                                                    <input type="text" name="wa_meta_template_name" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_template_name'] ?? '' }}" placeholder="alim_lesson_reminder">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Bahasa Template</label>
                                                    <input type="text" name="wa_meta_template_language" class="form-control form-control-sm"
                                                           value="{{ $settings['wa_meta_template_language'] ?? 'id' }}" placeholder="id">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small d-block">&nbsp;</label>
                                                    <div class="form-text small mt-0">
                                                        Template pengingat 5 menit memakai 3 parameter:
                                                        <code>@{{1}}</code> mapel, <code>@{{2}}</code> kelas,
                                                        <code>@{{3}}</code> jam mulai.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <h6 class="mb-0"><i class="ri-links-line me-1"></i>Matriks Channel Notifikasi</h6>
                                                <small class="text-muted">
                                                    Pilih channel untuk tiap jenis notifikasi. <strong>Sistem</strong> = lonceng in-app.
                                                    Channel Email/WhatsApp tetap mengikuti preferensi masing-masing pengguna.
                                                </small>
                                            </div>
                                            <button type="button" id="enable-all-notif" class="btn btn-sm btn-soft-success flex-shrink-0">
                                                <i class="ri-check-double-line me-1"></i>Aktifkan semua
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            @php
                                                $channelMatrix = json_decode($settings['notification_channel_matrix'] ?? '[]', true) ?: [];
                                                $allChannelsOff = ! empty($channelMatrix)
                                                    && collect($channelMatrix)->every(fn ($channels) => ! collect($channels)->contains(true));
                                            @endphp
                                            @if($allChannelsOff)
                                                <div class="alert alert-warning small rounded-0 border-0 border-bottom mb-0">
                                                    <i class="ri-error-warning-line me-1"></i>
                                                    Semua channel notifikasi sedang <strong>nonaktif</strong>. Notifikasi tidak akan tampil di lonceng dan tidak dikirim ke email/WhatsApp.
                                                    Klik <strong>Aktifkan semua</strong>, lalu simpan.
                                                </div>
                                            @endif
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Jenis Notifikasi</th>
                                                            @foreach(config('notifications.channels') as $channelKey => $channelMeta)
                                                                <th class="text-center" style="width:110px">
                                                                    <i class="{{ $channelMeta['icon'] }} me-1"></i>{{ $channelMeta['label'] }}
                                                                </th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach(config('notifications.events') as $eventKey => $event)
                                                            <tr>
                                                                <td>
                                                                    <div class="fw-medium small">{{ $event['label'] }}</div>
                                                                    <div class="text-muted" style="font-size:.7rem">{{ $event['group'] }}</div>
                                                                </td>
                                                                @foreach(config('notifications.channels') as $channelKey => $channelMeta)
                                                                    <td class="text-center">
                                                                        <input class="form-check-input" type="checkbox"
                                                                               name="notif_channels[{{ $eventKey }}][{{ $channelKey }}]" value="1"
                                                                               {{ ($channelMatrix[$eventKey][$channelKey] ?? true) ? 'checked' : '' }}>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header border-bottom-dashed">
                                            <h6 class="mb-0"><i class="ri-file-text-line me-1"></i>Template Pesan</h6>
                                            <small class="text-muted">Biarkan kosong untuk memakai template bawaan. Placeholder: {santri} {waktu} {penjemput} {tujuan} {estimasi} {catatan} {mapel} {rombel} {judul} {tanggal}</small>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                @foreach(['picked_up' => 'Dijemput', 'returned' => 'Kembali ke asrama', 'sick' => 'Sakit', 'rapor' => 'Rapor siap', 'holiday' => 'Libur/Jadwal', 'permit_approved' => 'Izin disetujui', 'permit_rejected' => 'Izin ditolak', 'violation' => 'Pelanggaran', 'overdue' => 'Belum kembali (overdue)', 'achievement' => 'Prestasi', 'announcement' => 'Pengumuman', 'visit' => 'Kunjungan'] as $tkey => $tlabel)
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <label class="form-label small mb-1">{{ $tlabel }}</label>
                                                            <div class="form-check form-switch mb-1">
                                                                <input class="form-check-input" type="checkbox" name="wa_event_{{ $tkey }}" value="1"
                                                                    {{ ($settings['wa_event_'.$tkey] ?? '1') == '1' ? 'checked' : '' }}>
                                                                <label class="form-check-label small">Aktif</label>
                                                            </div>
                                                        </div>
                                                        <textarea name="wa_template_{{ $tkey }}" rows="3" class="form-control form-control-sm"
                                                            placeholder="{{ config('whatsapp.templates.'.$tkey) }}">{{ $settings['wa_template_'.$tkey] ?? '' }}</textarea>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-history-line me-1"></i>Riwayat Pengiriman Terakhir</h6></div>
                                        <div class="card-body">
                                            @if(!empty($waLogs) && $waLogs->count() > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light"><tr><th>Waktu</th><th>Nomor</th><th>Event</th><th>Status</th><th>Pratinjau</th><th>Kirim Manual</th></tr></thead>
                                                        <tbody>
                                                            @foreach($waLogs as $log)
                                                                <tr>
                                                                    <td class="small">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m H:i') }}</td>
                                                                    <td class="small">{{ $log->phone }}</td>
                                                                    <td class="small">{{ $log->event ?? '-' }}</td>
                                                                    <td><span class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'logged' ? 'info' : 'danger') }}-subtle text-{{ $log->status === 'sent' ? 'success' : ($log->status === 'logged' ? 'info' : 'danger') }}">{{ $log->status }}</span></td>
                                                                    <td class="small">{{ \Illuminate\Support\Str::limit($log->message, 70) }}@if(!empty($log->error))<div class="text-danger" style="font-size:0.7rem">{{ \Illuminate\Support\Str::limit($log->error, 90) }}</div>@endif</td>
                                                                    <td class="text-center">
                                                                        <a href="https://wa.me/{{ $log->phone }}?text={{ rawurlencode((string) $log->message) }}"
                                                                           target="_blank" rel="noopener" class="btn btn-sm btn-soft-success" title="Buka WhatsApp untuk kirim manual">
                                                                            <i class="ri-whatsapp-line"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted small mb-0">Belum ada riwayat pengiriman.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Integrasi POS --}}
                        @if($tab === 'pos-api')
                            @php
                                $posEnabled = \App\Support\PosApiConfig::enabled();
                                $posKey = \App\Support\PosApiConfig::key();
                                $posHeader = \App\Support\PosApiConfig::header();
                                $posAllowedIps = implode(',', \App\Support\PosApiConfig::allowedIps());
                                $posGeneratedAt = $settings['pos_api_key_generated_at'] ?? null;
                                $posBaseUrl = url('/api/pos/v1');
                                $posKeyFromEnv = ! \App\Support\PosApiConfig::hasStoredKey() && $posKey !== '';
                                $posKeyEncrypted = \App\Support\PosApiConfig::isEncrypted($settings['pos_api_key'] ?? null);
                                $posEndpoints = [
                                    ['ping', 'Tes koneksi + ringkasan jumlah data', 'Untuk tombol "Tes Koneksi" di aplikasi POS'],
                                    ['satuan-kerja', 'Satuan kerja / unit kerja', 'Filter: type, divisi_id, is_active, search'],
                                    ['kepegawaian', 'Data pegawai (GTK): biodata, no. WhatsApp, satuan kerja', 'Filter: satuan_kerja, status_kepegawaian, jenis_gtk, jabatan, updated_since, search'],
                                    ['divisi', 'Master divisi', 'Filter: is_active, search'],
                                    ['jabatan', 'Master jabatan & jenis GTK', 'Filter: jenis_gtk_id, is_active, search'],
                                    ['jenis-gtk', 'Master jenis GTK', 'Filter: is_active, search'],
                                    ['sekolah', 'Profil sekolah / lembaga', 'Filter: school_level, work_unit_id, is_active, search'],
                                    ['santri', 'Data santri: NIS/NISN, kelas, sekolah, status', 'Filter: status, sekolah, kelas, jenis_kelamin, updated_since, search'],
                                ];
                            @endphp

                            <input type="hidden" name="pos_tab" value="1">

                            <div class="row g-4">
                                <div class="col-lg-7">
                                    {{-- Token --}}
                                    <div class="card border mb-4">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-key-2-line me-1"></i>Token API</h6>
                                            <span class="badge bg-{{ $posEnabled ? 'success' : 'secondary' }}-subtle text-{{ $posEnabled ? 'success' : 'secondary' }}">
                                                {{ $posEnabled ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <label class="form-label small">Token</label>
                                            <div class="input-group">
                                                <input type="password" id="pos-api-key" class="form-control font-monospace"
                                                    value="{{ $posKey }}" readonly autocomplete="off">
                                                <button type="button" class="btn btn-soft-secondary" onclick="posToggleKey()"
                                                    title="Tampilkan / sembunyikan">
                                                    <i class="ri-eye-line" id="pos-api-key-icon"></i>
                                                </button>
                                                <button type="button" class="btn btn-soft-primary" onclick="posCopy(this)">
                                                    <i class="ri-file-copy-line me-1"></i>Salin
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                                                <small class="text-muted">
                                                    Sumber:
                                                    @if($posKeyFromEnv)
                                                        <span class="badge bg-warning-subtle text-warning">.env — belum disimpan ke database</span>
                                                    @elseif($posKey !== '')
                                                        <span class="badge bg-info-subtle text-info">Database</span>
                                                        @if($posKeyEncrypted)
                                                            <span class="badge bg-success-subtle text-success" title="Token disimpan terenkripsi dengan APP_KEY">
                                                                <i class="ri-lock-2-line me-1"></i>terenkripsi
                                                            </span>
                                                        @endif
                                                        @if($posGeneratedAt)
                                                            <span class="text-muted">· dibuat {{ \Illuminate\Support\Carbon::parse($posGeneratedAt)->format('d/m/Y H:i') }}</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Belum ada token</span>
                                                    @endif
                                                </small>
                                                <small class="text-muted">Header: <code>{{ $posHeader }}</code></small>
                                            </div>
                                            <hr class="my-3">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <button type="submit" name="regenerate_pos_key" value="1" class="btn btn-sm btn-warning"
                                                    onclick="return confirm('Token lama akan langsung tidak berlaku dan POS harus diganti tokennya. Lanjutkan?')">
                                                    <i class="ri-refresh-line me-1"></i>Generate Token Baru
                                                </button>
                                                <span class="text-muted small">Gunakan bila token bocor atau saat serah terima ke tim POS.</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Pengaturan akses --}}
                                    <div class="card border">
                                        <div class="card-header border-bottom-dashed">
                                            <h6 class="mb-0"><i class="ri-shield-check-line me-1"></i>Pengaturan Akses</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="pos_api_enabled" value="1"
                                                            id="pos-enabled" {{ $posEnabled ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="pos-enabled">Aktifkan API POS</label>
                                                    </div>
                                                    <small class="text-muted">Jika dimatikan, semua request ke <code>/api/pos/v1/*</code> ditolak (503).</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Nama Header Token</label>
                                                    <input type="text" name="pos_api_header" class="form-control form-control-sm font-monospace"
                                                        value="{{ $posHeader }}" placeholder="X-ALIM-POS-KEY">
                                                    <small class="text-muted">POS mengirim token lewat header ini.</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Whitelist IP (opsional)</label>
                                                    <textarea name="pos_api_allowed_ips" rows="2" class="form-control form-control-sm font-monospace"
                                                        placeholder="103.10.20.30, 10.0.0.0/8">{{ $posAllowedIps }}</textarea>
                                                    <small class="text-muted">Kosongkan = semua IP boleh (cukup token). Isi bila server POS ber-IP tetap.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    {{-- Alamat API --}}
                                    <div class="card border mb-4">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-links-line me-1"></i>Alamat API</h6>
                                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="posCopyUrl(this)">
                                                <i class="ri-file-copy-line me-1"></i>Salin
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div class="p-2 bg-light rounded font-monospace small text-break" id="pos-base-url">{{ $posBaseUrl }}</div>
                                            <small class="text-muted">Tambahkan <code>?page=1&per_page=100</code> untuk menarik data per halaman. Wajib HTTPS di produksi.</small>
                                        </div>
                                    </div>

                                    {{-- Dokumentasi singkat --}}
                                    <div class="card border">
                                        <div class="card-header border-bottom-dashed">
                                            <h6 class="mb-0"><i class="ri-code-s-slash-line me-1"></i>Contoh Pemakaian</h6>
                                        </div>
                                        <div class="card-body">
                                            <pre class="bg-dark text-light p-3 rounded small mb-3" style="white-space: pre-wrap;">curl -H "{{ $posHeader }}: {{ $posKey ?: '<TOKEN>' }}" \
  "{{ $posBaseUrl }}/kepegawaian?per_page=100"</pre>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr><th>Endpoint</th><th>Isi</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($posEndpoints as [$path, $label, $hint])
                                                            <tr>
                                                                <td class="font-monospace small">GET /{{ $path }}</td>
                                                                <td class="small">
                                                                    {{ $label }}
                                                                    <div class="text-muted" style="font-size:0.72rem;">{{ $hint }}</div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Google Drive --}}
                        @if($tab === 'google-drive')
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="alert alert-info small"><i class="ri-information-line me-1"></i>Buat Service Account di Google Cloud Console, aktifkan Google Drive API, lalu bagikan folder Drive kepada email service account sebagai <strong>Editor</strong>. Tempel ID folder dan JSON service account di bawah.</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Status</label>
                                            <select name="google_drive_enabled" class="form-select">
                                                <option value="1" {{ ($settings['google_drive_enabled'] ?? '0') === '1' ? 'selected' : '' }}>Aktif</option>
                                                <option value="0" {{ ($settings['google_drive_enabled'] ?? '0') !== '1' ? 'selected' : '' }}>Nonaktif</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">ID Folder Google Drive</label>
                                            <input type="text" name="google_drive_folder_id" class="form-control" value="{{ $settings['google_drive_folder_id'] ?? '' }}" placeholder="1AbCdEfGhIjKlMnOpQrStUvWxYz">
                                            <small class="text-muted">Ambil dari URL folder: drive.google.com/drive/folders/<strong>ID_FOLDER</strong></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">URL Folder (opsional)</label>
                                            <input type="url" name="google_drive_folder_url" class="form-control" value="{{ $settings['google_drive_folder_url'] ?? '' }}" placeholder="https://drive.google.com/drive/folders/...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Shared Drive ID (opsional)</label>
                                            <input type="text" name="google_drive_shared_drive_id" class="form-control" value="{{ $settings['google_drive_shared_drive_id'] ?? '' }}" placeholder="Khusus Google Shared Drive">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Service Account JSON</label>
                                            <textarea name="google_drive_service_account_json" class="form-control font-monospace" rows="9" placeholder='{"type":"service_account","project_id":"...","private_key":"...","client_email":"..."}'>{{ old('google_drive_service_account_json') }}</textarea>
                                            <small class="text-muted">Kosongkan jika tidak ingin mengganti kredensial yang sudah tersimpan.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card border h-100">
                                        <div class="card-header"><h6 class="mb-0">Status Konfigurasi</h6></div>
                                        <div class="card-body small">
                                            <div class="mb-2 d-flex justify-content-between"><span class="text-muted">Status</span>@if(($settings['google_drive_enabled'] ?? '0') === '1')<span class="badge bg-success-subtle text-success">Aktif</span>@else<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>@endif</div>
                                            <div class="mb-2"><span class="text-muted">Service Account:</span><br><strong>{{ $settings['google_drive_service_account_email'] ?? 'Belum diset' }}</strong></div>
                                            <div class="mb-2"><span class="text-muted">Project ID:</span><br><strong>{{ $settings['google_drive_project_id'] ?? '-' }}</strong></div>
                                            <div class="mb-2"><span class="text-muted">Folder:</span><br><strong>{{ $settings['google_drive_folder_id'] ?? 'Belum diset' }}</strong></div>
                                            <div class="mb-3"><span class="text-muted">JSON Service Account:</span><br>@if(!empty($settings['google_drive_service_account_email']))<span class="badge bg-success-subtle text-success">Tersimpan terenkripsi</span>@else<span class="badge bg-danger-subtle text-danger">Belum diisi</span>@endif</div>
                                            @if(empty($settings['google_drive_service_account_email']))
                                                <div class="alert alert-warning py-2 px-2 mb-3" style="font-size:.75rem">Tempel JSON service account, lalu klik <strong>Test Koneksi Drive</strong>. Tombol Test akan menyimpan kredensial terlebih dahulu.</div>
                                            @endif
                                            <button type="submit" class="btn btn-outline-primary w-100" formaction="{{ route('user.sa.system-settings.test-google-drive', ['userId' => $userId]) }}"><i class="ri-plug-line me-1"></i>Test Koneksi Drive</button>
                                            <div class="text-muted mt-2" style="font-size:.72rem">Jika aktif, foto bukti diunggah ke Drive. Jika gagal, sistem fallback ke penyimpanan lokal.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Integrasi Recruitment --}}
                        @if($tab === 'recruitment-api')
                            @php
                                $recruitmentEnabled = \App\Support\RecruitmentApiConfig::enabled();
                                $recruitmentKey = \App\Support\RecruitmentApiConfig::key();
                                $recruitmentHeader = \App\Support\RecruitmentApiConfig::header();
                                $recruitmentAllowedIps = implode(',', \App\Support\RecruitmentApiConfig::allowedIps());
                                $recruitmentGeneratedAt = $settings['recruitment_api_key_generated_at'] ?? null;
                                $recruitmentBaseUrl = url('/api/recruitment/v1');
                                $recruitmentKeyFromEnv = ! \App\Support\RecruitmentApiConfig::hasStoredKey() && $recruitmentKey !== '';
                                $recruitmentKeyEncrypted = \App\Support\RecruitmentApiConfig::isEncrypted($settings['recruitment_api_key'] ?? null);
                                $recruitmentOutboundUrl = \App\Support\RecruitmentOutboundConfig::baseUrl();
                                $recruitmentHasOutboundToken = \App\Support\RecruitmentOutboundConfig::token() !== null;
                                $recruitmentOutboundFromEnv = ! \App\Support\RecruitmentOutboundConfig::hasStoredToken()
                                    && config('services.recruitment_api.token');
                                $recruitmentEndpoints = [
                                    ['ping', 'Tes koneksi + ringkasan data Personalia'],
                                    ['pegawai', 'Pegawai/GTK: email, no. WA, unit kerja, jabatan, role'],
                                    ['satuan-kerja', 'Master satuan kerja'],
                                    ['jabatan', 'Master jabatan'],
                                    ['jenis-gtk', 'Master jenis GTK'],
                                    ['sekolah', 'Profil sekolah/lembaga'],
                                    ['role', 'Daftar role & jumlah pengguna'],
                                ];
                            @endphp

                            <input type="hidden" name="recruitment_tab" value="1">

                            <div class="row g-4">
                                <div class="col-lg-7">
                                    <div class="card border mb-4">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-key-2-line me-1"></i>Token API Recruitment</h6>
                                            <span class="badge bg-{{ $recruitmentEnabled ? 'success' : 'secondary' }}-subtle text-{{ $recruitmentEnabled ? 'success' : 'secondary' }}">
                                                {{ $recruitmentEnabled ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <label class="form-label small">Token</label>
                                            <div class="input-group">
                                                <input type="password" id="recruitment-api-key" class="form-control font-monospace"
                                                    value="{{ $recruitmentKey }}" readonly autocomplete="off">
                                                <button type="button" class="btn btn-soft-secondary" onclick="recruitmentToggleKey()"
                                                    title="Tampilkan / sembunyikan">
                                                    <i class="ri-eye-line" id="recruitment-api-key-icon"></i>
                                                </button>
                                                <button type="button" class="btn btn-soft-primary" onclick="recruitmentCopy(this)">
                                                    <i class="ri-file-copy-line me-1"></i>Salin
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                                                <small class="text-muted">
                                                    Sumber:
                                                    @if($recruitmentKeyFromEnv)
                                                        <span class="badge bg-warning-subtle text-warning">.env — belum disimpan ke database</span>
                                                    @elseif($recruitmentKey !== '')
                                                        <span class="badge bg-info-subtle text-info">Database</span>
                                                        @if($recruitmentKeyEncrypted)
                                                            <span class="badge bg-success-subtle text-success"><i class="ri-lock-2-line me-1"></i>terenkripsi</span>
                                                        @endif
                                                        @if($recruitmentGeneratedAt)
                                                            <span class="text-muted">· dibuat {{ \Illuminate\Support\Carbon::parse($recruitmentGeneratedAt)->format('d/m/Y H:i') }}</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Belum ada token</span>
                                                    @endif
                                                </small>
                                                <small class="text-muted">Header: <code>{{ $recruitmentHeader }}</code></small>
                                            </div>
                                            <hr class="my-3">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <button type="submit" name="regenerate_recruitment_key" value="1" class="btn btn-sm btn-warning"
                                                    onclick="return confirm('Token lama akan langsung tidak berlaku dan aplikasi recruitment harus diganti tokennya. Lanjutkan?')">
                                                    <i class="ri-refresh-line me-1"></i>Generate Token Baru
                                                </button>
                                                <span class="text-muted small">Gunakan bila token bocor atau saat serah terima ke tim recruitment.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border">
                                        <div class="card-header border-bottom-dashed">
                                            <h6 class="mb-0"><i class="ri-shield-check-line me-1"></i>Pengaturan Akses</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="recruitment_api_enabled" value="1"
                                                            id="recruitment-enabled" {{ $recruitmentEnabled ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="recruitment-enabled">Aktifkan API Recruitment</label>
                                                    </div>
                                                    <small class="text-muted">Jika dimatikan, semua request ke <code>/api/recruitment/v1/*</code> ditolak (503).</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Nama Header Token</label>
                                                    <input type="text" name="recruitment_api_header" class="form-control form-control-sm font-monospace"
                                                        value="{{ $recruitmentHeader }}" placeholder="X-ALIM-RECRUITMENT-KEY">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Whitelist IP (opsional)</label>
                                                    <textarea name="recruitment_api_allowed_ips" rows="2" class="form-control form-control-sm font-monospace"
                                                        placeholder="103.10.20.30, 10.0.0.0/8">{{ $recruitmentAllowedIps }}</textarea>
                                                    <small class="text-muted">Kosongkan = semua IP boleh (cukup token).</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="card border mb-4">
                                        <div class="card-header border-bottom-dashed">
                                            <h6 class="mb-0"><i class="ri-arrow-left-right-line me-1"></i>Koneksi ke Aplikasi Recruitment (Outbound)</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label small">Base URL</label>
                                                    <input type="url" name="recruitment_outbound_url" class="form-control form-control-sm"
                                                        value="{{ $recruitmentOutboundUrl }}" placeholder="https://recruitment.contoh.id">
                                                    <small class="text-muted">Tanpa akhiran <code>/api</code>; sistem menormalisasi otomatis.</small>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small">Token (Bearer)</label>
                                                    <input type="password" name="recruitment_outbound_token" class="form-control form-control-sm"
                                                        placeholder="{{ $recruitmentHasOutboundToken ? '•••••• (kosongkan = tidak berubah)' : 'Token API recruitment' }}"
                                                        autocomplete="off">
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" name="recruitment_outbound_token_clear"
                                                            value="1" id="recruitment-outbound-token-clear">
                                                        <label class="form-check-label small" for="recruitment-outbound-token-clear">Hapus token tersimpan</label>
                                                    </div>
                                                    @if($recruitmentOutboundFromEnv)
                                                        <small class="text-muted">Sumber token saat ini: <code>.env</code></small>
                                                    @endif
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" name="test_recruitment_connection" value="1" class="btn btn-sm btn-soft-info">
                                                        <i class="ri-signal-tower-line me-1"></i>Tes Koneksi
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                Dipakai ALIM untuk mengambil dokumen/foto pelamar dan mengirim notifikasi.
                                                Client API (lamaran/lowongan) siap dipakai saat migrasi ATS selesai.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="card border mb-4">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-links-line me-1"></i>Alamat API</h6>
                                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="recruitmentCopyUrl(this)">
                                                <i class="ri-file-copy-line me-1"></i>Salin
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div class="p-2 bg-light rounded font-monospace small text-break" id="recruitment-base-url">{{ $recruitmentBaseUrl }}</div>
                                            <small class="text-muted">Tambahkan <code>?page=1&per_page=100</code>. Wajib HTTPS di produksi.</small>
                                        </div>
                                    </div>

                                    <div class="card border">
                                        <div class="card-header border-bottom-dashed">
                                            <h6 class="mb-0"><i class="ri-code-s-slash-line me-1"></i>Contoh & Endpoint</h6>
                                        </div>
                                        <div class="card-body">
                                            <pre class="bg-dark text-light p-3 rounded small mb-3" style="white-space: pre-wrap;">curl -H "{{ $recruitmentHeader }}: {{ $recruitmentKey ?: '<TOKEN>' }}" \
  "{{ $recruitmentBaseUrl }}/pegawai?per_page=100"</pre>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light"><tr><th>Endpoint</th><th>Isi</th></tr></thead>
                                                    <tbody>
                                                        @foreach($recruitmentEndpoints as [$path, $label])
                                                            <tr>
                                                                <td class="font-monospace small">GET /{{ $path }}</td>
                                                                <td class="small">{{ $label }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Cache & Utils --}}
                        @if($tab === 'cache')
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card border">
                                        <div class="card-header bg-light"><h6 class="mb-0">Cache Management</h6></div>
                                        <div class="card-body">
                                            <p class="text-muted small">Hapus semua cache aplikasi. Ini akan memaksa aplikasi memuat ulang konfigurasi.</p>
                                            <a href="{{ route('user.sa.system-settings.clear-cache', ['userId' => $userId]) }}"
                                                class="btn btn-warning"
                                                onclick="return confirm('Bersihkan semua cache?')">
                                                <i class="ri-delete-database-2-line me-1"></i> Clear All Cache
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($tab !== 'cache')
                            <div class="mt-4">
                                <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Pengaturan</button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@if($tab === 'notifications')
    @push('scripts')
        <script>
            document.getElementById('enable-all-notif')?.addEventListener('click', function () {
                document.querySelectorAll('input[name^="notif_channels["]').forEach(function (checkbox) {
                    checkbox.checked = true;
                });
            });
        </script>
    @endpush
@endif

@if($tab === 'pos-api')
    @push('scripts')
        <script>
            function posToggleKey() {
                const input = document.getElementById('pos-api-key');
                const icon = document.getElementById('pos-api-key-icon');
                if (!input) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('ri-eye-line');
                    icon.classList.add('ri-eye-off-line');
                } else {
                    input.type = 'password';
                    icon.classList.remove('ri-eye-off-line');
                    icon.classList.add('ri-eye-line');
                }
            }

            function posCopyText(text, button) {
                const done = function () {
                    if (typeof Toastify !== 'undefined') {
                        Toastify({
                            text: 'Tersalin ke clipboard',
                            duration: 1500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#0ab39c' }
                        }).showToast();
                    }
                    if (button) {
                        const original = button.innerHTML;
                        button.innerHTML = '<i class="ri-check-line me-1"></i>Tersalin';
                        setTimeout(function () { button.innerHTML = original; }, 1500);
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(done);
                } else {
                    const helper = document.createElement('textarea');
                    helper.value = text;
                    document.body.appendChild(helper);
                    helper.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(helper);
                    done();
                }
            }

            function posCopy(button) {
                const input = document.getElementById('pos-api-key');
                if (input) posCopyText(input.value, button);
            }

            function posCopyUrl(button) {
                const el = document.getElementById('pos-base-url');
                if (el) posCopyText(el.textContent.trim(), button);
            }
        </script>
    @endpush
@endif

@if($tab === 'recruitment-api')
    @push('scripts')
        <script>
            function recruitmentToggleKey() {
                const input = document.getElementById('recruitment-api-key');
                const icon = document.getElementById('recruitment-api-key-icon');
                if (!input) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('ri-eye-line');
                    icon.classList.add('ri-eye-off-line');
                } else {
                    input.type = 'password';
                    icon.classList.remove('ri-eye-off-line');
                    icon.classList.add('ri-eye-line');
                }
            }

            function recruitmentCopyText(text, button) {
                const done = function () {
                    if (typeof Toastify !== 'undefined') {
                        Toastify({
                            text: 'Tersalin ke clipboard',
                            duration: 1500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#0ab39c' }
                        }).showToast();
                    }
                    if (button) {
                        const original = button.innerHTML;
                        button.innerHTML = '<i class="ri-check-line me-1"></i>Tersalin';
                        setTimeout(function () { button.innerHTML = original; }, 1500);
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(done);
                } else {
                    const helper = document.createElement('textarea');
                    helper.value = text;
                    document.body.appendChild(helper);
                    helper.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(helper);
                    done();
                }
            }

            function recruitmentCopy(button) {
                const input = document.getElementById('recruitment-api-key');
                if (input) recruitmentCopyText(input.value, button);
            }

            function recruitmentCopyUrl(button) {
                const el = document.getElementById('recruitment-base-url');
                if (el) recruitmentCopyText(el.textContent.trim(), button);
            }
        </script>
    @endpush
@endif
