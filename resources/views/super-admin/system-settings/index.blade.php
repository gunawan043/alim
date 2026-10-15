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
                            <a class="nav-link {{ $tab === 'pos-api' ? 'active' : '' }}"
                                href="{{ route('user.sa.system-settings.index', ['userId' => $userId, 'tab' => 'pos-api']) }}">
                                <i class="ri-plug-line me-1"></i> Integrasi POS
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
                                                    id="wa-notif" {{ ($settings['whatsapp_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="wa-notif">Notifikasi WhatsApp</label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="push_notifications" value="1"
                                                    id="push-notif" {{ ($settings['push_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="push-notif">Push Notifications</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class="card h-100">
                                        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="ri-whatsapp-line me-1"></i>Gateway WhatsApp (tanpa API resmi)</h6>
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
                                                        @foreach(['log' => 'Log / Gratis (wa.me manual)', 'fonnte' => 'Fonnte', 'wablas' => 'Wablas', 'generic' => 'Generic Webhook'] as $value => $label)
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
                                                    <label class="form-label small">API Token</label>
                                                    <input type="text" name="wa_api_token" class="form-control form-control-sm" value="{{ $settings['wa_api_token'] ?? '' }}" placeholder="token Fonnte/Wablas">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">API URL (opsional)</label>
                                                    <input type="text" name="wa_api_url" class="form-control form-control-sm" value="{{ $settings['wa_api_url'] ?? '' }}" placeholder="https://api.fonnte.com/send">
                                                </div>
                                                <input type="hidden" name="wa_sender" value="{{ $settings['wa_sender'] ?? '' }}">
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
                                $posEnabled = ($settings['pos_api_enabled'] ?? '1') == '1';
                                $posKey = (string) ($settings['pos_api_key'] ?? config('services.pos.api_key'));
                                $posHeader = (string) ($settings['pos_api_header'] ?? config('services.pos.header', 'X-ALIM-POS-KEY'));
                                $posAllowedIps = (string) ($settings['pos_api_allowed_ips'] ?? implode(',', (array) config('services.pos.allowed_ips', [])));
                                $posGeneratedAt = $settings['pos_api_key_generated_at'] ?? null;
                                $posBaseUrl = url('/api/pos/v1');
                                $posKeyFromEnv = empty($settings['pos_api_key']) && $posKey !== '';
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
                                            <small class="text-muted">Tambahkan <code>?page=1&per_page=100</code> untuk menarik data per halaman.</small>
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
