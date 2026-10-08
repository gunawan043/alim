{{-- Daftar QR Kelas — satu QR per kelas --}}
@extends('layouts.master')

@section('title', 'QR Kelas')

@push('css')
<style>
    .qr-thumb { width: 52px; height: 52px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 3px; object-fit: contain; }
    .table-freeze th, .table-freeze td { vertical-align: middle; }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Absensi Kehadiran @endslot
    @slot('li_2') QR Kelas @endslot
    @slot('title') QR Kelas @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted small mb-0">
        Setiap kelas memiliki satu QR untuk absensi masuk/keluar guru
        @if($activeAy) • Tahun Ajaran {{ $activeAy->name }} @endif
    </p>
    <a href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
        <i class="ri-qr-scan-2-line me-1"></i>Halaman Scan Guru
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-qr-code-line text-primary me-1"></i>Daftar QR per Kelas</h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $studyGroups->count() }} kelas</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-freeze mb-0">
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
