@extends('layouts.master')
@section('title') Detail Mutasi Kamar — {{ $roomMove->resident?->student?->name ?? 'Mutasi' }} @endsection

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
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('li_4') Mutasi Kamar @endslot
        @slot('title') Detail @endslot
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
        {{-- ============================================================
             LEFT COLUMN — STUDENT & MOVE DETAIL
        ============================================================ --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-3">
                    <h5 class="mb-0 flex-grow-1">
                        <i class="ri-exchange-line me-2 text-primary"></i>Detail Permohonan Mutasi Kamar
                    </h5>
                    @if($roomMove->status === 'pending')
                        <span class="badge bg-warning-subtle text-warning">
                            <i class="ri-time-line me-1"></i>Menunggu
                        </span>
                    @elseif($roomMove->status === 'approved')
                        <span class="badge bg-success-subtle text-success">
                            <i class="ri-checkbox-circle-line me-1"></i>Disetujui
                        </span>
                    @elseif($roomMove->status === 'rejected')
                        <span class="badge bg-danger-subtle text-danger">
                            <i class="ri-close-circle-line me-1"></i>Ditolak
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-4">

                        {{-- Student Info --}}
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-user-follow-line me-1"></i>Informasi Santri
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Santri</label>
                            <div class="fw-semibold">{{ $roomMove->resident?->student?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">NISN</label>
                            <div class="fw-semibold">
                                <code>{{ $roomMove->resident?->student?->nisn ?? '—' }}</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Jenis Kelamin</label>
                            <div class="fw-semibold">
                                @if($roomMove->resident?->student?->gender === 'L')
                                    <span class="badge bg-primary-subtle text-primary">
                                        <i class="ri-men-line me-1"></i>Laki-laki
                                    </span>
                                @elseif($roomMove->resident?->student?->gender === 'P')
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="ri-women-line me-1"></i>Perempuan
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Kamar Saat Ini (Asal)</label>
                            <div class="fw-semibold">
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="ri-home-4-line me-1"></i>{{ $roomMove->fromRoom?->name ?? '—' }}
                                </span>
                            </div>
                        </div>

                        {{-- Move Detail --}}
                        <div class="col-12 mt-4">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-arrow-left-right-line me-1"></i>Detail Mutasi
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Kamar Tujuan</label>
                            <div class="fw-semibold">
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="ri-arrow-right-line me-1"></i>{{ $roomMove->toRoom?->name ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Tanggal Pindah</label>
                            <div class="fw-semibold">
                                @if($roomMove->move_date)
                                    {{ $roomMove->move_date->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Jenis Mutasi</label>
                            <div class="fw-semibold">
                                @php
                                    $typeLabels = [
                                        'reguler' => 'Reguler',
                                        'disciplinary' => 'Disipliner',
                                        'medical' => 'Medis',
                                        'upgrade' => 'Upgrade Kamar',
                                        'other' => 'Lainnya',
                                    ];
                                @endphp
                                <span class="badge bg-info-subtle text-info">
                                    {{ $typeLabels[$roomMove->move_type] ?? ucfirst($roomMove->move_type ?? '—') }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Status</label>
                            <div class="fw-semibold">
                                @if($roomMove->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="ri-time-line me-1"></i>Menunggu Persetujuan
                                    </span>
                                @elseif($roomMove->status === 'approved')
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="ri-checkbox-circle-line me-1"></i>Disetujui
                                    </span>
                                @elseif($roomMove->status === 'rejected')
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="ri-close-circle-line me-1"></i>Ditolak
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ ucfirst($roomMove->status ?? '—') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted small">Alasan Pindah</label>
                            <div class="fw-semibold">{{ $roomMove->reason ?? '—' }}</div>
                        </div>
                        @if($roomMove->notes)
                        <div class="col-md-12">
                            <label class="form-label text-muted small">Catatan</label>
                            <div class="fw-semibold">{{ $roomMove->notes }}</div>
                        </div>
                        @endif

                        {{-- Approval/Rejection Info --}}
                        @if($roomMove->approved_by || $roomMove->rejected_by)
                        <div class="col-12 mt-4">
                            <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2">
                                <i class="ri-shield-check-line me-1"></i>Persetujuan
                            </h6>
                        </div>
                        @if($roomMove->approved_by)
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Disetujui Oleh</label>
                            <div class="fw-semibold">{{ $roomMove->approver?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Tanggal Persetujuan</label>
                            <div class="fw-semibold">{{ $roomMove->approved_at?->format('d M Y, H:i') ?? '—' }}</div>
                        </div>
                        @endif
                        @if($roomMove->rejected_by)
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Ditolak Oleh</label>
                            <div class="fw-semibold">{{ $roomMove->rejecter?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Alasan Penolakan</label>
                            <div class="fw-semibold text-danger">{{ $roomMove->rejection_reason ?? '—' }}</div>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex gap-2 justify-content-between align-items-center flex-wrap">
                        <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                        <div class="d-flex gap-2 flex-wrap">

                            {{-- Reject (only pending) --}}
                            @if($roomMove->status === 'pending')
                                <button type="button" class="btn btn-outline-danger" onclick="showRejectModal()">
                                    <i class="ri-close-line me-1"></i> Tolak
                                </button>
                            @endif

                            {{-- Approve (only pending) --}}
                            @if($roomMove->status === 'pending')
                                <form method="POST"
                                      action="{{ route('user.asrama.room-moves.approve', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomMoveUuid' => $roomMove->id]) }}"
                                      id="approveForm">
                                    @csrf
                                    <div class="input-group">
                                        <input type="text"
                                               name="approval_note"
                                               class="form-control"
                                               placeholder="Catatan persetujuan (opsional)"
                                               style="max-width: 220px;">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-checkbox-line me-1"></i> Setujui
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reject Modal --}}
            @if($roomMove->status === 'pending')
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rejectModalLabel">
                                <i class="ri-close-circle-line me-2 text-danger"></i>Tolak Permohonan
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST"
                              action="{{ route('user.asrama.room-moves.reject', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomMoveUuid' => $roomMove->id]) }}">
                            @csrf
                            <div class="modal-body">
                                <p>Yakin ingin menolak permohonan mutasi kamar ini?</p>
                                <div class="mb-3">
                                    <label class="form-label" for="rejection_reason">
                                        Alasan Penolakan <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="rejection_reason"
                                              id="rejection_reason"
                                              class="form-control"
                                              rows="3"
                                              placeholder="Jelaskan alasan penolakan..."
                                              required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="ri-close-line me-1"></i> Tolak Permohonan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- ============================================================
             RIGHT COLUMN — STATUS TIMELINE
        ============================================================ --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="ri-timeline me-2"></i>Status Permohonan</h5>
                </div>
                <div class="card-body p-3">
                    <div class="status-step mb-4 {{ $roomMove->status === 'pending' ? 'text-muted' : '' }}">
                        <div class="status-step-dot
                            {{ $roomMove->status !== 'pending' ? 'bg-success text-white' : 'bg-light text-muted border' }}">
                            @if(in_array($roomMove->status, ['approved','rejected']))
                                <i class="ri-check-line"></i>
                            @endif
                        </div>
                        <div class="fw-semibold mb-0">Diajukan</div>
                        <div class="text-muted small">{{ $roomMove->created_at->format('d M Y, H:i') }}</div>
                    </div>

                    <div class="status-step mb-4 {{ !in_array($roomMove->status, ['approved','rejected']) ? 'text-muted' : '' }}">
                        <div class="status-step-dot
                            {{ $roomMove->status === 'approved' ? 'bg-success text-white' : '' }}
                            {{ $roomMove->status === 'rejected' ? 'bg-danger text-white' : '' }}
                            {{ !in_array($roomMove->status, ['approved','rejected']) ? 'bg-light text-muted border' : '' }}">
                            @if($roomMove->status === 'approved')
                                <i class="ri-check-line"></i>
                            @elseif($roomMove->status === 'rejected')
                                <i class="ri-close-line"></i>
                            @endif
                        </div>
                        <div class="fw-semibold mb-0">
                            {{ $roomMove->status === 'rejected' ? 'Ditolak' : 'Disetujui' }}
                        </div>
                        <div class="text-muted small">
                            @if($roomMove->status === 'approved')
                                {{ $roomMove->approved_at?->format('d M Y, H:i') ?? $roomMove->updated_at->format('d M Y, H:i') }}
                            @elseif($roomMove->status === 'rejected')
                                {{ $roomMove->updated_at->format('d M Y, H:i') }}
                            @else
                                Menunggu
                            @endif
                        </div>
                        @if($roomMove->approver?->name || $roomMove->rejecter?->name)
                            <div class="text-muted small mt-1">
                                <i class="ri-user-line me-1"></i>
                                {{ $roomMove->approver?->name ?? $roomMove->rejecter?->name ?? '' }}
                            </div>
                        @endif
                    </div>

                    @if($roomMove->status === 'approved')
                    <div class="status-step text-muted">
                        <div class="status-step-dot bg-light text-muted border">
                            <i class="ri-checkbox-line"></i>
                        </div>
                        <div class="fw-semibold mb-0">Selesai Dipindahkan</div>
                        <div class="text-muted small">
                            @if($roomMove->move_date)
                                {{ $roomMove->move_date->format('d M Y') }}
                            @else
                                Menunggu tanggal
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick Info --}}
            <div class="card mt-3">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="ri-file-info-line me-2"></i>Info Lainnya</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">ID Mutasi</label>
                        <div class="fw-semibold"><code>{{ $roomMove->id }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Asrama</label>
                        <div class="fw-semibold">{{ $dormitory->name ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Pengaju</label>
                        <div class="fw-semibold">{{ $roomMove->requester?->name ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Dibuat</label>
                        <div class="fw-semibold small">{{ $roomMove->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small">Terakhir Update</label>
                        <div class="fw-semibold small">{{ $roomMove->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
function showRejectModal() {
    var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endsection
