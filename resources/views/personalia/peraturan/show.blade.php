@extends('layouts.master')
@section('title') Detail Peraturan @endsection

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp
@include('components.personalia-page-header', [
    'title' => $peraturan->judul,
    'description' => $peraturan->kategori->nama ?? 'Tanpa Kategori',
    'icon' => 'ri-file-shield-line',
    'iconColor' => '#06b6d4',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Peraturan', 'url' => route('user.ats.peraturan.index', $userId)],
        ['label' => 'Detail'],
    ],
])

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-file-text-line me-1"></i>Informasi Dokumen</h5>
                @switch($peraturan->status)
                    @case('aktif')    <span class="badge bg-success-subtle text-success">Aktif</span> @break
                    @case('nonaktif') <span class="badge bg-secondary">Nonaktif</span> @break
                    @case('draft')    <span class="badge bg-secondary-subtle text-secondary">Draft</span> @break
                    @case('revisi')   <span class="badge bg-warning-subtle text-warning">Revisi</span> @break
                @endswitch
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Nomor Dokumen</label><div class="fw-semibold">{{ $peraturan->nomor_dokumen ?? '-' }}</div></div>
                    <div class="col-md-3"><label class="text-muted" style="font-size:.78rem">Versi</label><div class="fw-semibold">v{{ $peraturan->versi ?? '1.0' }}</div></div>
                    <div class="col-md-3"><label class="text-muted" style="font-size:.78rem">Kategori</label><div class="fw-semibold">{{ $peraturan->kategori->nama ?? '-' }}</div></div>
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Berlaku Mulai</label><div>{{ $peraturan->tanggal_berlaku?->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Berlaku Sampai</label><div>{{ $peraturan->tanggal_expired?->format('d M Y') ?? 'Tidak terbatas' }}</div></div>
                    <div class="col-12">
                        <label class="text-muted" style="font-size:.78rem">Deskripsi</label>
                        <div class="p-2 bg-light rounded">{{ $peraturan->deskripsi ?? '—' }}</div>
                    </div>
                    @if($peraturan->catatan_perubahan)
                    <div class="col-12">
                        <label class="text-muted" style="font-size:.78rem">Catatan Perubahan</label>
                        <div class="p-2 bg-warning-subtle rounded">{{ $peraturan->catatan_perubahan }}</div>
                    </div>
                    @endif
                    @if($peraturan->dokumen_path)
                    <div class="col-12">
                        <a href="{{ asset('storage/' . $peraturan->dokumen_path) }}" target="_blank" class="btn btn-soft-primary btn-sm">
                            <i class="ri-file-pdf-line me-1"></i> Buka Dokumen
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('user.ats.peraturan.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                <a href="{{ route('user.ats.peraturan.edit', [$userId, $peraturan->id]) }}" class="btn btn-primary btn-sm"><i class="ri-edit-line me-1"></i> Edit</a>
                <form action="{{ route('user.ats.peraturan.acknowledge', [$userId, $peraturan->id]) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="ri-check-double-line me-1"></i> Saya Sudah Membaca</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-eye-line me-1"></i>Log Pembacaan</h5></div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h2 class="fw-bold mb-0">{{ $totalBaca }}</h2>
                    <small class="text-muted">GTK sudah membaca</small>
                </div>
                <hr>
                @forelse($readLogs as $log)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-semibold" style="width:32px;height:32px;font-size:.75rem">
                        {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold" style="font-size:.82rem">{{ $log->user->name ?? '-' }}</div>
                        <small class="text-muted">{{ $log->read_at->format('d M Y H:i') }}</small>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3">Belum ada yang membaca</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
