@extends('layouts.master')
@section('title')
    Pengaturan Pensiun
@endsection

@section('content')
@php $userId = $userId ?? request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb')
    @slot('li_1') GTK @endslot
    @slot('li_2') Pensiun @endslot
    @slot('title') Pengaturan @endslot
@endcomponent

<div class="alert alert-info mb-3" role="alert">
    <i class="ri-information-line me-1"></i>
    Pengaturan berlaku untuk seluruh GTK. BUP default: <strong>58 tahun</strong> | Notifikasi default: <strong>6 bulan</strong> sebelum BUP.
</div>

<form method="POST" action="{{ route('user.pension.settings.update', ['userId' => $userId]) }}">
    @csrf

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-time-line me-1"></i>Pengaturan Usia Pensiun
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">Batas Usia Pensiun (BUP)</label>
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" name="bup_age" class="form-control"
                                       value="{{ old('bup_age', $settings['bup_age'] ?? 58) }}"
                                       min="40" max="70" required>
                                <span class="input-group-text">tahun</span>
                            </div>
                            @error('bup_age')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Umur Early Retirement</label>
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" name="early_retirement_age" class="form-control"
                                       value="{{ old('early_retirement_age', $settings['early_retirement_age'] ?? 55) }}"
                                       min="30" max="65">
                                <span class="input-group-text">tahun</span>
                            </div>
                            @error('early_retirement_age')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Tahun Sebelum BUP untuk Early</label>
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" name="early_retirement_years" class="form-control"
                                       value="{{ old('early_retirement_years', $settings['early_retirement_years'] ?? 2) }}"
                                       min="1" max="10">
                                <span class="input-group-text">tahun</span>
                            </div>
                            @error('early_retirement_years')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-notification-3-line me-1"></i>Pengaturan Notifikasi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4">
                            <label class="form-label">Aktifkan Notifikasi</label>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notification_enabled"
                                       id="notification_enabled"
                                       value="1"
                                       {{ old('notification_enabled', $settings['notification_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notification_enabled">
                                    {{ old('notification_enabled', $settings['notification_enabled'] ?? '1') === '1' ? 'Aktif' : 'Nonaktif' }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-lg-4">
                            <label class="form-label">Bulan Sebelum BUP</label>
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" name="notification_months" class="form-control"
                                       value="{{ old('notification_months', $settings['notification_months'] ?? 6) }}"
                                       min="1" max="24" required>
                                <span class="input-group-text">bulan</span>
                            </div>
                            @error('notification_months')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-wallet-line me-1"></i>Pengaturan Benefit
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">Minimum Masa Kerja</label>
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" name="min_service_years" class="form-control"
                                       value="{{ old('min_service_years', $settings['min_service_years'] ?? 10) }}"
                                       min="0" max="50">
                                <span class="input-group-text">tahun</span>
                            </div>
                            @error('min_service_years')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Persentase Pensiun (% dari Gaji)</label>
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" name="pension_percentage" class="form-control"
                                       value="{{ old('pension_percentage', $settings['pension_percentage'] ?? 75) }}"
                                       min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('pension_percentage')
                            <span class="text-danger" style="font-size:0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('user.pension.index', ['userId' => $userId]) }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="ri-save-line align-middle me-1"></i> Simpan Pengaturan
        </button>
    </div>
</form>
@endsection
