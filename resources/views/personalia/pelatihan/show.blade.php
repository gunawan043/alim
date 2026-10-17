{{-- Pelatihan: Detail Pelatihan --}}
@extends('layouts.master')
@section('title') Detail Pelatihan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#f5f7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
.info-row{padding:.55rem 0;border-bottom:1px solid #f1f5f9}
.info-row:last-child{border-bottom:none}
.info-label{font-weight:600;color:#475569;font-size:.8rem;text-transform:uppercase;letter-spacing:.4px;width:160px}
.participant-card{transition:all .2s ease}
.participant-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
</style>
@endpush

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $statusMap = [
        'draft'      => ['Draft',      'secondary'],
        'ditetapkan' => ['Ditetapkan', 'primary'],
        'selesai'    => ['Selesai',    'success'],
        'dibatalkan' => ['Dibatalkan', 'danger'],
    ];
    [$statusLabel, $statusColor] = $statusMap[$pelatihan->status ?? 'draft'] ?? ['-', 'secondary'];

    $participantStatuses = [
        'daftar'     => 'secondary',
        'diterima'   => 'primary',
        'ditolak'    => 'danger',
        'hadir'      => 'success',
        'tidak_hadir'=> 'warning',
    ];
@endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('li_2') Pelatihan @endslot
    @slot('title') {{ $pelatihan->nama }} @endslot
@endcomponent

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#6366f118;color:#4f46e5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-graduation-cap-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">{{ $pelatihan->nama }}</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">
                {{ ucfirst($pelatihan->kategori ?? 'internal') }} • {{ ucfirst($pelatihan->jenis ?? 'pelatihan') }}
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} ms-2">{{ $statusLabel }}</span>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.pelatihan.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
        <a href="{{ route('user.pelatihan.peserta', [$userId, $pelatihan->id]) }}" class="btn btn-info btn-sm"><i class="ri-group-line me-1"></i>Peserta ({{ $participantCount }})</a>
        <a href="{{ route('user.pelatihan.edit', [$userId, $pelatihan->id]) }}" class="btn btn-primary btn-sm"><i class="ri-edit-line me-1"></i>Edit</a>
    </div>
</div>

<div class="row g-4">
    {{-- Detail Info --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-information-line text-primary me-1"></i>Informasi Pelatihan</h5>
            </div>
            <div class="card-body">
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Nama</div>
                    <div class="flex-grow-1">
                        <strong>{{ $pelatihan->nama }}</strong>
                    </div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Kategori</div>
                    <div class="flex-grow-1">{{ ucfirst($pelatihan->kategori ?? '-') }}</div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Jenis</div>
                    <div class="flex-grow-1">{{ ucfirst($pelatihan->jenis ?? '-') }}</div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Vendor</div>
                    <div class="flex-grow-1">{{ $pelatihan->vendor ?? '-' }}</div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Tanggal</div>
                    <div class="flex-grow-1">
                        {{ $pelatihan->tanggal_mulai ? \Carbon\Carbon::parse($pelatihan->tanggal_mulai)->format('d F Y') : '-' }}
                        @if($pelatihan->tanggal_selesai && $pelatihan->tanggal_selesai != $pelatihan->tanggal_mulai)
                            s/d {{ \Carbon\Carbon::parse($pelatihan->tanggal_selesai)->format('d F Y') }}
                        @endif
                    </div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Lokasi</div>
                    <div class="flex-grow-1">{{ $pelatihan->lokasi ?? '-' }}</div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Kapasitas</div>
                    <div class="flex-grow-1">{{ $pelatihan->kapasitas ?? '-' }}</div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Biaya</div>
                    <div class="flex-grow-1">
                        @if($pelatihan->biaya)
                            Rp {{ number_format((float)$pelatihan->biaya, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Status</div>
                    <div class="flex-grow-1">
                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $statusLabel }}</span>
                    </div>
                </div>
                <div class="info-row d-flex align-items-start">
                    <div class="info-label">Dibuat Oleh</div>
                    <div class="flex-grow-1">{{ optional($pelatihan->createdBy)->name ?? optional($pelatihan->pembuat)->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        {{-- Deskripsi --}}
        <div class="card mt-4">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-file-text-line text-primary me-1"></i>Deskripsi</h5>
            </div>
            <div class="card-body">
                @if($pelatihan->deskripsi)
                    {!! nl2br(e($pelatihan->deskripsi)) !!}
                @else
                    <p class="text-muted mb-0 small fst-italic">Tidak ada deskripsi</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Sidebar --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-dashboard-line text-primary me-1"></i>Statistik</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="text-muted small">Total Peserta</div>
                    <div class="fw-bold fs-5">{{ $participantCount }}</div>
                </div>
                @foreach(['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak Hadir', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak'] as $key => $label)
                    @php $count = $participants->where('status', $key)->count(); @endphp
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">{{ $label }}</span>
                        <span class="badge bg-{{ $participantStatuses[$key] ?? 'secondary' }}-subtle text-{{ $participantStatuses[$key] ?? 'secondary' }}">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
            @if($pelatihan->materi_path)
                <div class="card-footer bg-light-subtle">
                    <a href="{{ Storage::url($pelatihan->materi_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                        <i class="ri-file-download-line me-1"></i>Unduh Materi
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Participants List --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ri-group-line text-primary me-1"></i>Daftar Peserta</h5>
                <a href="{{ route('user.pelatihan.peserta', [$userId, $pelatihan->id]) }}" class="btn btn-light btn-sm">
                    Kelola Peserta <i class="ri-arrow-right-line ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="bg-light" style="width:48px">No</th>
                            <th class="bg-light">Peserta</th>
                            <th class="bg-light text-center">Tanggal Kehadiran</th>
                            <th class="bg-light text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participants as $peserta)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs rounded-circle bg-indigo-subtle text-indigo d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                            {{ strtoupper(substr($peserta->gtk?->nama ?? $peserta->nama ?? 'P', 0, 1)) }}
                                        </div>
                                        <span class="fw-medium">{{ $peserta->gtk?->nama ?? $peserta->nama ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="text-center small">
                                    {{ $peserta->tanggal_kehadiran ? \Carbon\Carbon::parse($peserta->tanggal_kehadiran)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="text-center">
                                    @php $status = $peserta->status ?? 'daftar'; @endphp
                                    <span class="badge bg-{{ $participantStatuses[$status] ?? 'secondary' }}-subtle text-{{ $participantStatuses[$status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ', $status)) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div style="color:#6366f1;opacity:.4"><i class="ri-group-line" style="font-size:2.5rem"></i></div>
                                    <h6 class="mt-2 mb-0 fw-semibold">Belum ada peserta</h6>
                                    <p class="text-muted small mb-0">Daftarkan peserta dari menu Peserta</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
