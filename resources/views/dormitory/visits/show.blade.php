@extends('layouts.master')
@section('title') Detail Kunjungan — {{ $visit->visitor_name ?? 'Visit' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('css')
<style>
    .status-step { position: relative; padding-left: 2rem; }
    .status-step::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 28px;
        bottom: -16px;
        width: 2px;
        background: var(--bs-border-color);
    }
    .status-step:last-child::before { display: none; }
    .status-step-dot {
        position: absolute;
        left: 0;
        top: 4px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id]) }}">{{ $visit->dormitory?->name ?? 'Asrama' }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id]) }}">Kunjungan</a> @endslot
        @slot('title') Detail Kunjungan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row">
        {{-- Main Info --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-3">
                    <h5 class="mb-0 flex-grow-1">
                        <i class="ri-user-location-line me-2 text-primary"></i>Detail Kunjungan
                    </h5>
                    <div>{!! $visit->status_badge !!}</div>
                </div>
                <div class="card-body">
                    <div class="row g-4">

                        {{-- Visitor Info --}}
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-user-settings-line me-1"></i>Informasi Tamu
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Tamu</label>
                            <div class="fw-semibold">{{ $visit->visitor_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nomor Identitas</label>
                            <div class="fw-semibold">{{ $visit->visitor_id_number ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nomor Telepon</label>
                            <div class="fw-semibold">{{ $visit->visitor_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Hubungan</label>
                            <div>
                                @if($visit->visitor_relationship === 'mahrom')
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="ri-shield-star-line me-1"></i>Mahrom
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $visit->visitor_relationship ?? '—')) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Student Info --}}
                        <div class="col-12 mt-4">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-user-follow-line me-1"></i>Informasi Santri
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Santri</label>
                            <div class="fw-semibold">{{ $visit->student?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">NISN</label>
                            <div class="fw-semibold">{{ $visit->student?->nisn ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Asrama</label>
                            <div class="fw-semibold">{{ $visit->dormitory?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Jenis Kelamin</label>
                            <div class="fw-semibold">
                                <span class="badge bg-{{ $visit->student?->gender === 'L' ? 'primary' : 'danger' }}-subtle text-{{ $visit->student?->gender === 'L' ? 'primary' : 'danger' }}">
                                    <i class="ri-{{ $visit->student?->gender === 'L' ? 'men' : 'women' }}-line me-1"></i>
                                    {{ $visit->student?->gender_text ?? '—' }}
                                </span>
                            </div>
                        </div>

                        {{-- Visit Detail --}}
                        <div class="col-12 mt-4">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-calendar-check-line me-1"></i>Detail Kunjungan
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Tujuan</label>
                            <div class="fw-semibold">{{ $visit->purpose_text ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Durasi Rencana</label>
                            <div class="fw-semibold">
                                @if($visit->expected_duration_minutes)
                                    {{ $visit->expected_duration_minutes }} menit
                                    ({{ round($visit->expected_duration_minutes / 60, 1) }} jam)
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Waktu Datang (Rencana)</label>
                            <div class="fw-semibold">
                                @if($visit->expected_arrival)
                                    {{ $visit->expected_arrival->format('d M Y, H:i') }} WIB
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Waktu Datang (Aktual)</label>
                            <div class="fw-semibold">
                                @if($visit->actual_arrival_at)
                                    <span class="text-success">{{ $visit->actual_arrival_at->format('d M Y, H:i') }} WIB</span>
                                @else
                                    <span class="text-muted">Belum check-in</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Waktu Pergi (Aktual)</label>
                            <div class="fw-semibold">
                                @if($visit->actual_departure_at)
                                    <span class="text-info">{{ $visit->actual_departure_at->format('d M Y, H:i') }} WIB</span>
                                @else
                                    <span class="text-muted">Belum check-out</span>
                                @endif
                            </div>
                        </div>
                        @if($visit->notes)
                        <div class="col-12">
                            <label class="form-label text-muted small">Catatan</label>
                            <div class="fw-semibold">{{ $visit->notes }}</div>
                        </div>
                        @endif

                        {{-- Approved/Rejected Info --}}
                        @if($visit->approved_by || $visit->rejected_by)
                        <div class="col-12 mt-4">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-shield-check-line me-1"></i>Persetujuan
                            </h6>
                        </div>
                        @if($visit->approved_by)
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Disetujui Oleh</label>
                            <div class="fw-semibold">{{ $visit->approver?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Tanggal Persetujuan</label>
                            <div class="fw-semibold">{{ $visit->approved_at?->format('d M Y, H:i') ?? '—' }}</div>
                        </div>
                        @endif
                        @if($visit->rejected_by)
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Ditolak Oleh</label>
                            <div class="fw-semibold">{{ $visit->rejecter?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Alasan Penolakan</label>
                            <div class="fw-semibold text-danger">{{ $visit->rejection_reason ?? '—' }}</div>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex gap-2 justify-content-between align-items-center flex-wrap">
                        <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                        <div class="d-flex gap-2">
                            @if($visit->status === 'pending')
                                {{-- Reject --}}
                                <form method="POST"
                                      action="{{ route('user.asrama.visits.reject', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}"
                                      id="rejectForm">
                                    @csrf
                                    <button type="button" class="btn btn-outline-danger" onclick="confirmReject()">
                                        <i class="ri-close-line me-1"></i> Tolak
                                    </button>
                                </form>
                                {{-- Approve --}}
                                <form method="POST"
                                      action="{{ route('user.asrama.visits.approve', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="ri-checkbox-line me-1"></i> Setujui
                                    </button>
                                </form>
                            @endif
                            @if($visit->status === 'approved')
                                {{-- Check-in --}}
                                <form method="POST"
                                      action="{{ route('user.asrama.visits.checkin', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-login-box-line me-1"></i> Check-in
                                    </button>
                                </form>
                            @endif
                            @if($visit->status === 'arrived')
                                {{-- Check-out --}}
                                <form method="POST"
                                      action="{{ route('user.asrama.visits.checkout', ['userId' => $userId, 'asramaUuid' => $visit->dormitory_id, 'visitUuid' => $visit->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="ri-logout-box-r-line me-1"></i> Check-out
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar: Status Timeline --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="ri-timeline me-2"></i>Status Kunjungan</h5>
                </div>
                <div class="card-body p-3">
                    <div class="status-step mb-4 {{ $visit->status === 'pending' ? 'text-muted' : '' }}">
                        <div class="status-step-dot {{ $visit->status !== 'pending' ? 'bg-success text-white' : 'bg-light text-muted border' }}">
                            @if(in_array($visit->status, ['approved','arrived','checked_out']))
                                <i class="ri-check-line"></i>
                            @endif
                        </div>
                        <div class="fw-semibold mb-0">Diajukan</div>
                        <div class="text-muted small">{{ $visit->created_at->format('d M Y, H:i') }}</div>
                    </div>

                    <div class="status-step mb-4 {{ !in_array($visit->status, ['approved','arrived','checked_out']) ? 'text-muted' : '' }}">
                        <div class="status-step-dot
                            {{ $visit->status === 'approved' ? 'bg-success text-white' : '' }}
                            {{ $visit->status === 'rejected' ? 'bg-danger text-white' : '' }}
                            {{ in_array($visit->status, ['arrived','checked_out']) ? 'bg-success text-white' : '' }}
                            {{ !in_array($visit->status, ['approved','rejected','arrived','checked_out']) ? 'bg-light text-muted border' : '' }}">
                            @if(in_array($visit->status, ['arrived','checked_out']))
                                <i class="ri-check-line"></i>
                            @endif
                        </div>
                        <div class="fw-semibold mb-0">
                            {{ $visit->status === 'rejected' ? 'Ditolak' : 'Disetujui' }}
                        </div>
                        <div class="text-muted small">
                            @if($visit->approved_at)
                                {{ $visit->approved_at->format('d M Y, H:i') }}
                            @elseif($visit->status === 'rejected')
                                {{ $visit->updated_at->format('d M Y, H:i') }}
                            @else
                                Menunggu
                            @endif
                        </div>
                    </div>

                    <div class="status-step mb-4 {{ !in_array($visit->status, ['arrived','checked_out']) ? 'text-muted' : '' }}">
                        <div class="status-step-dot
                            {{ $visit->status === 'arrived' ? 'bg-success text-white' : '' }}
                            {{ $visit->status === 'checked_out' ? 'bg-success text-white' : '' }}
                            {{ !in_array($visit->status, ['arrived','checked_out']) ? 'bg-light text-muted border' : '' }}">
                            @if($visit->status === 'checked_out')
                                <i class="ri-check-line"></i>
                            @endif
                        </div>
                        <div class="fw-semibold mb-0">Tamu Tiba</div>
                        <div class="text-muted small">
                            @if($visit->actual_arrival_at)
                                {{ $visit->actual_arrival_at->format('d M Y, H:i') }}
                            @else
                                Menunggu
                            @endif
                        </div>
                    </div>

                    <div class="status-step {{ $visit->status !== 'checked_out' ? 'text-muted' : '' }}">
                        <div class="status-step-dot
                            {{ $visit->status === 'checked_out' ? 'bg-success text-white' : 'bg-light text-muted border' }}">
                            @if($visit->status === 'checked_out')
                                <i class="ri-check-line"></i>
                            @endif
                        </div>
                        <div class="fw-semibold mb-0">Selesai / Check-out</div>
                        <div class="text-muted small">
                            @if($visit->actual_departure_at)
                                {{ $visit->actual_departure_at->format('d M Y, H:i') }}
                            @else
                                Menunggu
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Info Card --}}
            <div class="card mt-3">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="ri-file-info-line me-2"></i>Info Lainnya</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">ID Kunjungan</label>
                        <div class="fw-semibold"><code>{{ $visit->id }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Asrama</label>
                        <div class="fw-semibold">{{ $visit->dormitory?->name ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Dibuat</label>
                        <div class="fw-semibold small">{{ $visit->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small">Terakhir Update</label>
                        <div class="fw-semibold small">{{ $visit->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
function confirmReject() {
    Swal.fire({
        title: 'Tolak Kunjungan?',
        text: 'Apakah Anda yakin ingin menolak kunjungan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-close-line me-1"></i> Ya, Tolak',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            var form = document.getElementById('rejectForm');
            // Add rejection reason if needed
            var reason = prompt('Alasan penolakan (opsional):');
            if (reason !== null) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'rejection_reason';
                input.value = reason;
                form.appendChild(input);
            }
            form.submit();
        }
    });
}
</script>
@endsection