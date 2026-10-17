{{-- Absensi GTK: Pengaturan --}}
@extends('layouts.master')
@section('title') Pengaturan Absensi GTK @endsection

@push('css')
<style>
.setting-item{border-left:3px solid #64748b;transition:border-color .2s ease}
.setting-item:hover{border-left-color:#2563eb}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Kehadiran GTK @endslot
    @slot('li_2') Pengaturan @endslot
    @slot('title') Pengaturan Absensi GTK @endslot
@endcomponent

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" style="background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%);border:1px solid #cbd5e1;padding:1.25rem 1.5rem;border-radius:.625rem">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#64748b18;color:#475569;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-settings-3-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Pengaturan Absensi GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Konfigurasi aturan dan parameter absensi GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.absensi-gtk.harian', $userId) }}" class="btn btn-light btn-sm"><i class="ri-calendar-check-line me-1"></i>Kehadiran</a>
        <a href="{{ route('user.absensi-gtk.index', $userId) }}" class="btn btn-outline-primary btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-4">
    {{-- Left Column: Settings Form --}}
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-qr-scan-2-line text-primary me-1"></i> Toleransi Keterlambatan &amp; Lokasi Scan QR</h5>
            </div>
            <div class="card-body">
                @if(! ($canManageQr ?? false))
                    <div class="alert alert-warning small mb-0">
                        <i class="ri-lock-line me-1"></i> Hanya satuan pendidikan berwenang (TU, Kepala/Wakil, Kurikulum) yang dapat mengubah pengaturan ini.
                    </div>
                @else
                    <form action="{{ route('user.absensi-gtk.settings.qr', $userId) }}" method="POST" id="qrSettingsForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Toleransi Keterlambatan</label>
                                <input type="number" name="late_threshold_minutes" class="form-control form-control-sm" min="0" max="120" required
                                       value="{{ old('late_threshold_minutes', $qrTime['qr_late_threshold_minutes'] ?? 5) }}">
                                <div class="form-text small">Menit setelah jam mulai → <strong>Terlambat</strong>. Default 5.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Toleransi Scan Awal</label>
                                <input type="number" name="tolerance_before_minutes" class="form-control form-control-sm" min="0" max="120" required
                                       value="{{ old('tolerance_before_minutes', $qrTime['qr_tolerance_before_minutes'] ?? 15) }}">
                                <div class="form-text small">Boleh scan sebelum jam mulai. Default 15.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Batas Scan Terlambat</label>
                                <input type="number" name="tolerance_after_minutes" class="form-control form-control-sm" min="0" max="120" required
                                       value="{{ old('tolerance_after_minutes', $qrTime['qr_tolerance_after_minutes'] ?? 5) }}">
                                <div class="form-text small">Setelah ini scan masuk ditolak. Default 5.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Toleransi Pulang Cepat</label>
                                <input type="number" name="early_leave_threshold_minutes" class="form-control form-control-sm" min="0" max="180" required
                                       value="{{ old('early_leave_threshold_minutes', $qrTime['qr_early_leave_threshold_minutes'] ?? 10) }}">
                                <div class="form-text small">Scan keluar lebih awal dari ini → <strong>Pulang Cepat</strong>. Default 10.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="geoEnabledSwitch" name="geo_enabled" value="1"
                                           {{ old('geo_enabled', $qrGeo['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="geoEnabledSwitch">Wajibkan verifikasi lokasi saat scan QR</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="deviceBindingSwitch" name="device_binding_enabled" value="1"
                                           {{ old('device_binding_enabled', \App\Models\AbsensiGtkSetting::get(\App\Services\TeacherDeviceBinding::KEY_ENABLED, false)) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="deviceBindingSwitch">Kunci perangkat (opsional)</label>
                                </div>
                                <div class="form-text small">Perangkat pertama yang dipakai GTK didaftarkan otomatis; scan berikutnya wajib dari perangkat yang sama. Ganti perangkat hanya melalui TU.</div>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-3" id="geoFields">
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Lintang (Latitude)</label>
                                <input type="text" name="geo_lat" id="geoLat" class="form-control form-control-sm"
                                       value="{{ old('geo_lat', $qrGeo['lat'] ?? '') }}" placeholder="-8.5833">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Bujur (Longitude)</label>
                                <input type="text" name="geo_lng" id="geoLng" class="form-control form-control-sm"
                                       value="{{ old('geo_lng', $qrGeo['lng'] ?? '') }}" placeholder="116.1167">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Radius (meter)</label>
                                <input type="number" name="geo_radius_m" class="form-control form-control-sm" min="10" max="5000"
                                       value="{{ old('geo_radius_m', $qrGeo['radius_m'] ?? 150) }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="useCurrentLocation()">
                                    <i class="ri-focus-3-line me-1"></i>Gunakan Lokasi Saya
                                </button>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border small mb-0">
                                    <i class="ri-information-line me-1 text-primary"></i>
                                    Titik &amp; radius dipakai <strong>backend</strong> untuk memvalidasi scan. Koordinat guru
                                    <strong>tidak disimpan</strong> — hanya keputusan izin. Lokasi bukan bukti anti-palsu; deteksi berbasis kejujuran perangkat.
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i>Simpan Pengaturan QR</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i> Konfigurasi</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.absensi-gtk.settings.store', $userId) }}" method="POST">
                    @csrf
                    <div class="alert alert-info small">
                        <i class="ri-information-line me-1"></i>
                        Setting yang tersimpan menggunakan sistem key-value. Setiap item memiliki key unik yang digunakan sebagai identifier.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Key</label>
                        <input type="text" name="items[0][key]" class="form-control form-control-sm" placeholder="Contoh: jam_masuk_default" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Value</label>
                        <input type="text" name="items[0][value]" class="form-control form-control-sm" placeholder="Nilai setting">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Type</label>
                        <select name="items[0][type]" class="form-select form-select-sm">
                            <option value="string">String</option>
                            <option value="int">Integer</option>
                            <option value="bool">Boolean</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i>Simpan</button>
                        <button type="reset" class="btn btn-light btn-sm"><i class="ri-reset-left-line me-1"></i>Reset</button>
                    </div>
                </form>

                <hr>
                <p class="text-muted small mb-3">Atau gunakan form inline di bawah untuk menambah setting baru:</p>

                <form action="{{ route('user.absensi-gtk.settings.store', $userId) }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-0 small">Key Baru</label>
                            <input type="text" name="items[0][key]" class="form-control form-control-sm" placeholder="key_baru">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-0 small">Value</label>
                            <input type="text" name="items[0][value]" class="form-control form-control-sm" placeholder="nilai">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0 small">Type</label>
                            <select name="items[0][type]" class="form-select form-select-sm">
                                <option value="string">String</option>
                                <option value="int">Integer</option>
                                <option value="bool">Boolean</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success btn-sm w-100"><i class="ri-add-line me-1"></i>Tambah Setting</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right Column: Existing Settings --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-list-check text-primary me-1"></i> Daftar Setting</h5>
            </div>
            <div class="card-body p-0">
                @forelse($settings as $setting)
                    <div class="setting-item px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">{{ $setting->key }}</div>
                            <div class="text-muted small" style="font-size:.72rem">
                                {{ $setting->value ?? '<empty>' }}
                                <span class="text-secondary ms-1">({{ $setting->type ?? 'string' }})</span>
                            </div>
                        </div>
                        <span class="badge bg-light text-muted small">{{ $setting->type ?? 'string' }}</span>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="ri-settings-3-line" style="font-size:2rem;opacity:.3"></i>
                        <p class="mt-2 mb-0">Belum ada setting</p>
                    </div>
                @endforelse
            </div>
            @if($settings->count() > 0)
                <div class="card-footer text-muted small">
                    Total: {{ $settings->count() }} setting
                </div>
            @endif
        </div>
    </div>
</div>
@if(session('success'))
    <x-modal-success id="qrSettingsSavedModal" title="Pengaturan Tersimpan!" message="{{ session('success') }}" />
@endif

@push('scripts')
<script src="https://cdn.lordicon.com/lordicon.js"></script>
@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('qrSettingsSavedModal');
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
    });
</script>
@endif
<script>
function useCurrentLocation() {
    var latEl = document.getElementById('geoLat');
    var lngEl = document.getElementById('geoLng');
    if (!latEl || !lngEl) return;

    if (!('geolocation' in navigator)) {
        alert('Perangkat tidak mendukung layanan lokasi.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            latEl.value = pos.coords.latitude.toFixed(6);
            lngEl.value = pos.coords.longitude.toFixed(6);
            var sw = document.getElementById('geoEnabledSwitch');
            if (sw) sw.checked = true;
        },
        function () {
            alert('Lokasi tidak terdeteksi. Pastikan izin lokasi aktif lalu coba lagi.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>
@endpush
@endsection
