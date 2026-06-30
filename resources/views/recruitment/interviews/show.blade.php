{{-- Interviews: Detail Interview --}}
@extends('layouts.master')
@section('title') Detail Interview @endsection

@push('css')
<style>
.event-banner{background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border:1px solid #7dd3fc;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .event-banner{background:linear-gradient(135deg,#082f49 0%,#0c4a6e 100%);border-color:#0284c7}
.detail-section{border-left:3px solid #0ea5e9;margin-bottom:.75rem}
.score-card{text-align:center;padding:1rem;border-radius:.5rem;background:#f8fafc}
</style>
@endpush

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $statusColors = [
        'menunggu'   => 'warning',
        'sedang_berlangsung' => 'primary',
        'lolos'      => 'success',
        'tidak_lolos'=> 'danger',
        'selesai'    => 'success',
    ];
    $statusColor = $statusColors[$interview->status ?? 'menunggu'] ?? 'secondary';
    $statusLabel = $interview->status ? ucwords(str_replace('_',' ', $interview->status)) : '-';
    $stageName = $interview->recruitmentPipelineStage?->nama_tahapan ?? '-';
@endphp

@component('components.breadcrumb')
    @slot('li_1') Rekrutmen (@ts) @endslot
    @slot('li_2') Interviews @endslot
    @slot('title') Detail Interview @endslot
@endcomponent

<div class="event-banner mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#0284c718;color:#0369a1;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-video-chat-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">{{ $stageName }}</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">
                {{ $interview->recruitmentApplication?->recruitmentProfile?->user?->name ?? '-' }}
                &middot;
                {{ $interview->recruitmentApplication?->recruitmentJob?->judul ?? '-' }}
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} ms-2">{{ $statusLabel }}</span>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.ats.interviews.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-4">
    {{-- Candidate Info --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-user-follow-line text-primary me-1"></i>Informasi Kandidat</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;font-size:1.5rem">
                        <i class="ri-user-line"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $interview->recruitmentApplication?->recruitmentProfile?->user?->name ?? '-' }}</h5>
                        <p class="text-muted mb-0 small">{{ $interview->recruitmentApplication?->recruitmentJob?->judul ?? '-' }}</p>
                    </div>
                </div>
                <div class="info-row d-flex align-items-start mb-2 pb-2 border-bottom">
                    <div class="text-muted small" style="width:130px">No. Lamaran</div>
                    <div class="fw-medium small">{{ $interview->recruitmentApplication?->no_lamaran ?? '-' }}</div>
                </div>
                <div class="info-row d-flex align-items-start mb-2 pb-2 border-bottom">
                    <div class="text-muted small" style="width:130px">Status</div>
                    <div><span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $statusLabel }}</span></div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="text-muted small" style="width:130px">Email</div>
                    <div class="small">{{ $interview->recruitmentApplication?->recruitmentProfile?->user?->email ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Schedule Info --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-calendar-event-line text-primary me-1"></i>Jadwal & Detail</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="score-card">
                            <div class="text-muted small mb-1">Mulai</div>
                            <div class="fw-bold text-dark">
                                <i class="ri-time-line text-primary me-1"></i>
                                {{ $interview->jadwal_mulai ? \Carbon\Carbon::parse($interview->jadwal_mulai)->format('d M Y H:i') : '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="score-card">
                            <div class="text-muted small mb-1">Selesai (estimasi)</div>
                            <div class="fw-bold text-dark">
                                @if($interview->jadwal_selesai)
                                    <i class="ri-flag-line text-success me-1"></i>
                                    {{ \Carbon\Carbon::parse($interview->jadwal_selesai)->format('d M Y H:i') }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="ri-map-pin-line text-info me-2 mt-1"></i>
                            <div>
                                <div class="text-muted small mb-1">Lokasi</div>
                                <div class="fw-medium small">{{ $interview->lokasi ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="ri-user-star-line text-warning me-2 mt-1"></i>
                            <div>
                                <div class="text-muted small mb-1">Penilai</div>
                                <div class="fw-medium small">{{ $interview->penilai?->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-start">
                            <i class="ri-question-line text-purple me-2 mt-1"></i>
                            <div>
                                <div class="text-muted small mb-1">Jenis Tahapan</div>
                                <div class="fw-medium small">{{ $stageName }}</div>
                            </div>
                        </div>
                    </div>
                    @if($interview->catatan)
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <i class="ri-file-text-line text-muted me-2 mt-1"></i>
                                <div>
                                    <div class="text-muted small mb-1">Catatan</div>
                                    <div class="small">{{ $interview->catatan }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if(isset($interview->nilai) && $interview->nilai !== null)
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <i class="ri-trophy-line text-warning me-2 mt-1"></i>
                                <div>
                                    <div class="text-muted small mb-1">Nilai</div>
                                    <div class="fw-bold fs-5">
                                        <span class="{{ $interview->nilai >= 60 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($interview->nilai, 2) }}
                                        </span>
                                        <span class="text-muted">/ 100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card mt-4">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-flashlight-line text-warning me-1"></i>Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('user.ats.interviews.show', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-refresh-line me-1"></i>Refresh</a>
                    @if($interview->status === 'menunggu' || $interview->status === 'sedang_berlangsung')
                        <button class="btn btn-success btn-sm" onclick="markComplete()">
                            <i class="ri-check-double-line me-1"></i>Selesai & Berikan Nilai
                        </button>
                    @endif
                    <button class="btn btn-outline-warning btn-sm" onclick="rescheduleInterview()">
                        <i class="ri-calendar-todo-line me-1"></i>Reschedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function markComplete() {
    Swal.fire({
        title: 'Masukkan Hasil',
        html: `
            <label class="form-label small">Hasil</label>
            <select id="hasil" class="form-select form-select-sm mb-2">
                <option value="lolos">Lolos</option>
                <option value="tidak_lolos">Tidak Lolos</option>
            </select>
            <label class="form-label small">Nilai (0-100)</label>
            <input id="nilai" type="number" class="form-control form-control-sm mb-2" min="0" max="100">
            <label class="form-label small">Feedback</label>
            <textarea id="feedback" class="form-control" rows="3"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            return {
                hasil: document.getElementById('hasil').value,
                nilai: document.getElementById('nilai').value,
                feedback: document.getElementById('feedback').value
            }
        }
    }).then(result => {
        if (result.isConfirmed) {
            alert('Fitur ini memerlukan backend endpoint POST /interviews/{id}/complete')
        }
    })
}

function rescheduleInterview() {
    Swal.fire({
        title: 'Ubah Jadwal',
        html: `
            <label class="form-label small">Jadwal Baru</label>
            <input id="jadwal" type="datetime-local" class="form-control form-control-sm mb-2">
            <label class="form-label small">Alasan Perubahan</label>
            <textarea id="alasan" class="form-control" rows="3"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ubah',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            alert('Fitur ini memerlukan backend endpoint POST /interviews/{id}/reschedule')
        }
    })
}
</script>
@endpush
@endsection
