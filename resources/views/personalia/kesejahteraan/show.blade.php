@extends('layouts.master')
@section('title') Detail Kesejahteraan @endsection

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp
@include('components.personalia-page-header', [
    'title' => 'Detail Kesejahteraan',
    'description' => $penerima->user->name ?? '-',
    'icon' => 'ri-heart-pulse-line',
    'iconColor' => '#ec4899',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Kesejahteraan', 'url' => route('user.ats.kesejahteraan.index', $userId)],
        ['label' => 'Detail'],
    ],
])

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ri-heart-pulse-line me-1"></i>Informasi Bantuan</h5>
                <span class="badge bg-{{ $penerima->status === 'aktif' ? 'success' : ($penerima->status === 'selesai' ? 'secondary' : 'warning') }}-subtle text-{{ $penerima->status === 'aktif' ? 'success' : ($penerima->status === 'selesai' ? 'secondary' : 'warning') }}">{{ ucfirst($penerima->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">GTK</label><div class="fw-semibold">{{ $penerima->user->name ?? '-' }}</div></div>
                    <div class="col-md-6"><label class="text-muted" style="font-size:.78rem">Program</label><div class="fw-semibold">{{ $penerima->kesejahteraan->nama ?? '-' }}</div></div>
                    <div class="col-md-4"><label class="text-muted" style="font-size:.78rem">Tanggal Mulai</label><div>{{ $penerima->tanggal_mulai?->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><label class="text-muted" style="font-size:.78rem">Tanggal Selesai</label><div>{{ $penerima->tanggal_selesai?->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><label class="text-muted" style="font-size:.78rem">Nominal</label><div class="fw-semibold">{{ $penerima->nominal ? 'Rp ' . number_format($penerima->nominal, 0, ',', '.') : '-' }}</div></div>
                    <div class="col-12"><label class="text-muted" style="font-size:.78rem">Keterangan</label><div class="p-2 bg-light rounded">{{ $penerima->keterangan ?? '—' }}</div></div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('user.ats.kesejahteraan.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                <a href="{{ route('user.ats.kesejahteraan.edit', [$userId, $penerima->id]) }}" class="btn btn-primary btn-sm"><i class="ri-edit-line me-1"></i> Edit</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-line me-1"></i>Klaim Terkait</h5></div>
            <div class="card-body">
                @php
                    $klaims = $penerima->klaims ?? \App\Models\KesejahteraanKlaim::where('kesejahteraan_penerima_id', $penerima->id)->orderBy('created_at','desc')->limit(5)->get();
                @endphp
                @forelse($klaims as $k)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold" style="font-size:.85rem">{{ $k->judul }}</div>
                        <small class="text-muted">{{ $k->created_at->format('d M Y') }}</small>
                    </div>
                    <span class="badge bg-{{ $k->status === 'approved' ? 'success' : ($k->status === 'rejected' ? 'danger' : 'warning') }}-subtle text-{{ $k->status === 'approved' ? 'success' : ($k->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($k->status) }}</span>
                </div>
                @empty
                <div class="text-center text-muted py-3">Belum ada klaim</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
