@extends('layouts.master')
@section('title') Detail Pengajuan Cuti @endsection
@section('css')
<style>
.timeline { position: relative; padding-left: 32px; }
.timeline::before { content: ''; position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.timeline-item { position: relative; padding-bottom: 18px; }
.timeline-item .dot { position: absolute; left: -28px; top: 0; width: 24px; height: 24px; border-radius: 50%; background: #fff; border: 3px solid #cbd5e1; display:flex; align-items:center; justify-content:center; font-size:11px; }
.timeline-item.done .dot { border-color: #10b981; color: #10b981; }
.timeline-item.rejected .dot { border-color: #ef4444; color: #ef4444; }
.timeline-item.current .dot { border-color: #f59e0b; color: #f59e0b; animation: pulse 1.6s infinite; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.5);} 50% { box-shadow: 0 0 0 6px rgba(245,158,11,0);} }
</style>
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? Auth::id();
    $sisaKuota = $sisaKuota ?? null;
@endphp
@include('components.personalia-page-header', [
    'title' => 'Detail Pengajuan Cuti',
    'description' => $cuti->user->name . ' • ' . $cuti->template->nama,
    'icon' => 'ri-calendar-check-line',
    'iconColor' => '#0ea5e9',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Cuti & Izin', 'url' => route('user.cuti.index', $userId)],
        ['label' => 'Detail'],
    ],
])

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-file-text-line me-1"></i>Informasi Pengajuan</h5>
                @switch($cuti->status)
                    @case('PENDING')   <span class="badge bg-warning-subtle text-warning">Menunggu</span> @break
                    @case('APPROVED')  <span class="badge bg-success-subtle text-success">Disetujui</span> @break
                    @case('REJECTED')  <span class="badge bg-danger-subtle text-danger">Ditolak</span> @break
                    @case('CANCELLED') <span class="badge bg-secondary">Dibatalkan</span> @break
                @endswitch
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted" style="font-size:.78rem">GTK</label>
                        <div class="fw-semibold">{{ $cuti->user->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted" style="font-size:.78rem">Jenis Cuti</label>
                        <div class="fw-semibold">{{ $cuti->template->nama ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted" style="font-size:.78rem">Tanggal Mulai</label>
                        <div class="fw-semibold">{{ $cuti->tanggal_mulai->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted" style="font-size:.78rem">Tanggal Selesai</label>
                        <div class="fw-semibold">{{ $cuti->tanggal_selesai->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted" style="font-size:.78rem">Durasi</label>
                        <div><span class="badge bg-info-subtle text-info">{{ $cuti->jumlah_hari }} hari</span></div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted" style="font-size:.78rem">Alasan</label>
                        <div class="p-2 bg-light rounded">{{ $cuti->alasan ?? '—' }}</div>
                    </div>
                    @if($cuti->attachment)
                    <div class="col-12">
                        <label class="text-muted" style="font-size:.78rem">Lampiran</label>
                        <div><a href="{{ asset('storage/' . $cuti->attachment) }}" target="_blank" class="btn btn-soft-primary btn-sm"><i class="ri-attachment-line me-1"></i> Lihat File</a></div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('user.cuti.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                @if($cuti->status === 'PENDING' && auth()->user()->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']))
                    <form action="{{ route('user.cuti.approve', [$userId, $cuti->id]) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-success btn-sm" onclick="return confirm('Setujui pengajuan ini?')"><i class="ri-check-line me-1"></i> Setujui</button>
                    </form>
                    <form action="{{ route('user.cuti.reject', [$userId, $cuti->id]) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="rejection_reason" value="Tidak disetujui">
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Tolak pengajuan ini?')"><i class="ri-close-line me-1"></i> Tolak</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-time-line me-1"></i> Timeline</h5></div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item done">
                        <div class="dot"><i class="ri-send-plane-fill"></i></div>
                        <div class="fw-semibold" style="font-size:.85rem">Diajukan</div>
                        <small class="text-muted">{{ $cuti->created_at->format('d M Y H:i') }}</small>
                    </div>
                    <div class="timeline-item {{ $cuti->status === 'PENDING' ? 'current' : ($cuti->status === 'REJECTED' ? 'rejected' : 'done') }}">
                        <div class="dot"><i class="{{ $cuti->status === 'REJECTED' ? 'ri-close-line' : 'ri-eye-line' }}"></i></div>
                        <div class="fw-semibold" style="font-size:.85rem">Review</div>
                        <small class="text-muted">Status: {{ $cuti->status_label }}</small>
                    </div>
                    @if($cuti->status === 'APPROVED')
                    <div class="timeline-item done">
                        <div class="dot"><i class="ri-check-line"></i></div>
                        <div class="fw-semibold" style="font-size:.85rem">Disetujui</div>
                        <small class="text-muted">{{ $cuti->approved_at?->format('d M Y H:i') }} oleh {{ $cuti->approver->name ?? '-' }}</small>
                    </div>
                    @endif
                    @if($cuti->status === 'REJECTED')
                    <div class="timeline-item rejected">
                        <div class="dot"><i class="ri-close-line"></i></div>
                        <div class="fw-semibold" style="font-size:.85rem">Ditolak</div>
                        <small class="text-muted">{{ $cuti->rejected_at?->format('d M Y H:i') }} — {{ $cuti->rejection_reason }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if(!is_null($sisaKuota))
        <div class="card">
            <div class="card-body">
                <div class="text-muted" style="font-size:.78rem">Sisa Kuota {{ $cuti->template->nama ?? '' }}</div>
                <h3 class="fw-bold mb-0">{{ $sisaKuota }} hari</h3>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
