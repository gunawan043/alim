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
@endsection
