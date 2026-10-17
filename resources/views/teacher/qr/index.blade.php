{{-- Daftar QR Kelas — satu QR per kelas --}}
@extends('layouts.master')

@section('title', 'QR Kelas')

@section('css')
    @include('kurikulum._styles')
    <style>
        .qr-thumb { width: 52px; height: 52px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 3px; object-fit: contain; }
    </style>
@endsection

@section('content')
@php
    $userId = $userId ?? auth()->id();
    $stats = $statistics ?? ['total' => 0, 'aktif' => 0, 'belum' => 0, 'wali_kelas' => 0];
    $qrStatus = request('qr_status');
@endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Kehadiran @endslot
    @slot('li_2') QR Kelas @endslot
    @slot('title') QR Kelas @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted small mb-0">
        Setiap kelas memiliki satu QR untuk absensi masuk/keluar guru
        @if($activeAy) • Tahun Ajaran {{ $activeAy->name }} @endif
    </p>
    <a href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
        <i class="ri-qr-scan-2-line me-1"></i>Halaman Scan Guru
    </a>
</div>

{{-- STATISTIK --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-building-4-line text-primary"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Kelas</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Kelas aktif di satuan pendidikan</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-qr-code-line text-success"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">QR Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['aktif']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-checkbox-circle-line me-1"></i>Siap dipindai guru</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Belum Dibuat</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['belum']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>QR dibuat otomatis saat dicetak</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-star-line text-info"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Wali Kelas</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['wali_kelas']) }}</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Kelas dengan wali kelas terdata</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-qr-code-line text-primary me-1"></i>Daftar QR per Kelas</h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $studyGroups->count() }} kelas</span>
    </div>

    <div class="card-header py-2 bg-light border-bottom">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
            <a href="{{ request()->fullUrlWithQuery(['qr_status' => null]) }}" class="filter-badge {{ ! $qrStatus ? 'active' : '' }}">Semua</a>
            <a href="{{ request()->fullUrlWithQuery(['qr_status' => 'aktif']) }}" class="filter-badge {{ $qrStatus === 'aktif' ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i> QR Aktif</a>
            <a href="{{ request()->fullUrlWithQuery(['qr_status' => 'belum']) }}" class="filter-badge {{ $qrStatus === 'belum' ? 'active' : '' }}"><i class="ri-time-line"></i> Belum Dibuat</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:48px" class="text-center">No</th>
                    <th style="width:70px" class="text-center">QR</th>
                    <th>Kelas</th>
                    <th>Tingkat</th>
                    <th>Wali Kelas</th>
                    <th class="text-center">Status QR</th>
                    <th>Terakhir Dibuat</th>
                    <th class="text-end" style="width:220px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studyGroups as $sg)
                    @php $token = $tokens[$sg->id] ?? null; @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">
                            <img src="{{ route('user.qr.image', ['userId' => $userId, 'study_group_id' => $sg->id]) }}"
                                 alt="QR {{ $sg->name }}" class="qr-thumb" loading="lazy">
                        </td>
                        <td class="fw-medium">{{ $sg->full_name ?? $sg->name }}</td>
                        <td>{{ $sg->gradeLevel->name ?? '-' }}</td>
                        <td>{{ $sg->homeroomTeacher?->name ?? '-' }}</td>
                        <td class="text-center">
                            @if($token)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Belum dibuat</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $token?->last_regenerated_at?->format('d M Y H:i') ?? $token?->created_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('user.qr.show', ['userId' => $userId, 'study_group_id' => $sg->id]) }}"
                               class="btn btn-sm btn-primary" title="Lihat & cetak QR">
                                <i class="ri-printer-line me-1"></i>Cetak
                            </a>
                            <form method="POST"
                                  action="{{ route('user.qr.regenerate', ['userId' => $userId, 'study_group_id' => $sg->id]) }}"
                                  class="d-inline js-regenerate">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Buat ulang QR"
                                        data-name="{{ $sg->full_name ?? $sg->name }}">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="ri-qr-code-line" style="font-size:2.5rem;opacity:.4"></i></div>
                            <h6 class="fw-semibold">Belum ada kelas aktif</h6>
                            <p class="text-muted small mb-0">Tambahkan rombongan belajar terlebih dahulu.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-2">
        <span class="small text-muted">
            <i class="ri-information-line me-1"></i>
            Alur: cetak QR kelas ini → tempel di depan kelas → guru memindai melalui menu <strong>Scan QR Kehadiran</strong>.
            Sistem mencatat jam masuk/keluar sesuai jadwal mengajar guru pada kelas tersebut.
        </span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-regenerate').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var name = form.querySelector('button[data-name]')?.dataset.name || 'kelas ini';
        if (! confirm('Buat ulang QR untuk ' + name + '? QR lama tidak akan berlaku lagi.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
