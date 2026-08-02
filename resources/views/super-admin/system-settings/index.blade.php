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
                            <a class="nav-link {{ $tab === 'security' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'security']) }}">
                                <i class="ri-shield-key-line me-1"></i> Keamanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === 'notifications' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'notifications']) }}">
                                <i class="ri-notification-3-line me-1"></i> Notifikasi
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

                        {{-- TAB: Security --}}
                        @if($tab === 'security')
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label">OTP Expiry (menit)</label>
                                    <input type="number" name="otp_expiry_minutes" class="form-control"
                                        value="{{ $settings['otp_expiry_minutes'] ?? 10 }}" min="1" max="60">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" class="form-control"
                                        value="{{ $settings['max_login_attempts'] ?? 5 }}" min="1" max="20">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Lockout Duration (menit)</label>
                                    <input type="number" name="lockout_duration_minutes" class="form-control"
                                        value="{{ $settings['lockout_duration_minutes'] ?? 15 }}" min="1" max="1440">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Session Lifetime (menit)</label>
                                    <input type="number" name="session_lifetime_minutes" class="form-control"
                                        value="{{ $settings['session_lifetime_minutes'] ?? 120 }}" min="1" max="10080">
                                    <small class="text-muted">Max: 10080 menit (7 hari)</small>
                                </div>
                            </div>
                        @endif

                        {{-- TAB: Notifications --}}
                        @if($tab === 'notifications')
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" value="1"
                                            id="email-notif" {{ ($settings['email_notifications'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email-notif">Aktifkan Notifikasi Email</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_notifications" value="1"
                                            id="wa-notif" {{ ($settings['whatsapp_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="wa-notif">Aktifkan Notifikasi WhatsApp</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="push_notifications" value="1"
                                            id="push-notif" {{ ($settings['push_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="push-notif">Aktifkan Push Notifications</label>
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
