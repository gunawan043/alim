@extends('layouts.master')
@section('title') Detail Kontrak Kerja @endsection
@section('css')
<style>
.timeline-item { position: relative; padding-left: 28px; padding-bottom: 16px; }
.timeline-item::before { content:''; position: absolute; left: 8px; top: 4px; width: 8px; height: 8px; background:#94a3b8; border-radius: 50%; }
.timeline-item.active::before { background:#10b981; }
</style>
@endsection

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp
@include('components.personalia-page-header', [
    'title' => 'Detail Kontrak Kerja',
    'description' => $kontrak->gtk->nama ?? '-',
    'icon' => 'ri-file-shield-2-line',
    'iconColor' => '#8b5cf6',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Kontrak Kerja', 'url' => route('user.ats.kontrak.index', $userId)],
        ['label' => 'Detail'],
    ],
])

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-file-shield-2-line me-1"></i>Informasi Kontrak</h5>
                <span class="badge bg-primary-subtle text-primary">{{ $kontrak->jenis_kontrak }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">GTK</label><div class="fw-semibold">{{ $kontrak->gtk->nama ?? '-' }}</div></div>
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Jabatan</label><div class="fw-semibold">{{ $kontrak->jabatan ?? '-' }}</div></div>
                    <div class="col-md-4"><label class="text-muted" style="font-size:.78rem">Tanggal Mulai</label><div class="fw-semibold">{{ $kontrak->tanggal_mulai->format('d M Y') }}</div></div>
                    <div class="col-md-4"><label class="text-muted" style="font-size:.78rem">Tanggal Selesai</label><div class="fw-semibold">{{ $kontrak->tanggal_selesai->format('d M Y') }}</div></div>
                    <div class="col-md-4"><label class="text-muted" style="font-size:.78rem">Durasi</label><div class="fw-semibold">{{ $kontrak->durasi_bulan }} bulan</div></div>
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Lokasi Kerja</label><div>{{ $kontrak->lokasi_kerja ?? '-' }}</div></div>
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Gaji Pokok</label><div>{{ $kontrak->gaji_pokok ? 'Rp ' . number_format($kontrak->gaji_pokok, 0, ',', '.') : '-' }}</div></div>
                    <div class="col-12"><label class="text-muted" style="font-size:.78rem">Catatan</label><div class="p-2 bg-light rounded">{{ $kontrak->catatan ?? '—' }}</div></div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('user.ats.kontrak.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                <a href="{{ route('user.ats.kontrak.edit', [$userId, $kontrak->id]) }}" class="btn btn-primary btn-sm"><i class="ri-edit-line me-1"></i> Edit</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-1"></i>Status</h5></div>
            <div class="card-body">
                @switch($kontrak->status)
                    @case('AKTIF')         <h4 class="text-success"><i class="ri-checkbox-circle-line"></i> Aktif</h4> @break
                    @case('SELESAI')       <h4 class="text-secondary"><i class="ri-checkbox-circle-line"></i> Selesai</h4> @break
                    @case('MENJADI_TETAP') <h4 class="text-primary"><i class="ri-shield-check-line"></i> Menjadi Tetap</h4> @break
                    @case('DIBATALKAN')    <h4 class="text-danger"><i class="ri-close-circle-line"></i> Dibatalkan</h4> @break
                @endswitch
                <hr>
                <div class="timeline-item active">
                    <div class="fw-semibold" style="font-size:.85rem">Mulai Kontrak</div>
                    <small class="text-muted">{{ $kontrak->tanggal_mulai->format('d M Y') }}</small>
                </div>
                <div class="timeline-item">
                    <div class="fw-semibold" style="font-size:.85rem">Berakhir</div>
                    <small class="text-muted">{{ $kontrak->tanggal_selesai->format('d M Y') }} ({{ $kontrak->tanggal_selesai->diffForHumans() }})</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
