@extends('layouts.master')
@section('title') Daftar Shift @endsection
@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:900px;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:11px 14px;word-break:break-word}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
.table-freeze tbody tr:hover td{background:#f1f5f9}
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#100c1f 0%,#150e22 100%);border-color:#5b21b6}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
$shiftPagi = \App\Models\Shift::where('nama','like','%Pagi%')->count();
$shiftSiang = \App\Models\Shift::where('nama','like','%Siang%')->count();
$shiftLain = \App\Models\Shift::count() - $shiftPagi - $shiftSiang;
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#8b5cf618;color:#7c3aed;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-team-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Daftar Shift</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Kelola jadwal shift kerja GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.jam-kerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Jam Kerja</a>
        @if($isAdmin)
        <a href="{{ route('user.jam-kerja.shift.create', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Tambah Shift</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-sun-line text-warning"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Shift Pagi</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $shiftPagi }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-sun-foggy-line text-info"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Shift Siang</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $shiftSiang }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card border-start border-4 border-secondary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-moon-line text-secondary"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Shift Lain</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $shiftLain }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i> Jadwal Shift GTK</h5>
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-1 small text-muted">
                <span class="badge bg-warning-subtle text-warning badge-status">Pagi</span>
                <span class="badge bg-info-subtle text-info badge-status">Siang</span>
                <span class="badge bg-secondary-subtle text-secondary badge-status">Lain</span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-freeze">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Nama Shift</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Durasi</th>
                        <th>Warna</th>
                        <th>Aktif</th>
                        <th class="no-print" style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $i => $s)
                    @php
                        $jamMasuk = \Carbon\Carbon::parse($s->jam_masuk);
                        $jamPulang = \Carbon\Carbon::parse($s->jam_pulang);
                        $durasi = $jamMasuk->diffInHours($jamPulang);
                    @endphp
                    <tr>
                        <td class="text-center text-muted" style="width:40px">{{ $shifts->firstItem() + $i }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="flex-shrink-0" style="width:12px;height:12px;border-radius:50%;background:{{ $s->warna ?? '#8b5cf6' }};display:inline-block"></span>
                                <span class="fw-semibold">{{ $s->nama }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><i class="ri-arrow-up-line me-1 text-success"></i>{{ $s->jam_masuk }}</span></td>
                        <td><span class="badge bg-light text-dark"><i class="ri-arrow-down-line me-1 text-danger"></i>{{ $s->jam_pulang }}</span></td>
                        <td><span class="badge bg-purple-subtle text-purple badge-status">{{ $durasi }} jam</span></td>
                        <td>
                            <span style="width:24px;height:24px;border-radius:50%;background:{{ $s->warna ?? '#8b5cf6' }};display:inline-block;border:2px solid #e2e8f0"></span>
                            <code class="ms-1 small" style="font-size:.72rem">{{ $s->warna ?? '#8b5cf6' }}</code>
                        </td>
                        <td>
                            @if($s->is_active)
                            <span class="badge bg-success-subtle text-success badge-status">Aktif</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary badge-status">Nonaktif</span>
                            @endif
                        </td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                                <a href="{{ route('user.jam-kerja.shift.edit', [$userId, $s->id]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-edit-2-line"></i></a>
                                <form action="{{ route('user.jam-kerja.shift.destroy', [$userId, $s->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus shift ini?')"><i class="ri-delete-bin-line"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <i class="ri-team-line text-muted" style="font-size:3rem;"></i>
                                <h5 class="fw-semibold text-dark mt-2 mb-1">Belum ada data shift</h5>
                                <p class="text-muted mb-0 small">Klik <strong>Tambah Shift</strong> untuk menambahkan shift baru.</p>
                                @if($isAdmin)<a href="{{ route('user.jam-kerja.shift.create', $userId) }}" class="btn btn-primary btn-sm mt-2"><i class="ri-add-line me-1"></i> Tambah Shift</a>@endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($shifts->hasPages())
    <div class="card-footer border-top-dashed bg-transparent no-print">
        <div class="d-flex justify-content-between align-items-center px-2">
            <p class="text-muted mb-0" style="font-size:.8rem">Menampilkan {{ $shifts->firstItem() }}–{{ $shifts->lastItem() }} dari {{ $shifts->total() }} data</p>
            {{ $shifts->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>

<div class="card mt-3 no-print">
    <div class="card-header border-bottom-dashed">
        <h5 class="card-title mb-0"><i class="ri-grid-line me-1"></i> Grid Waktu Shift</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered text-center mb-0" style="min-width:600px">
                <thead>
                    <tr>
                        <th>Shift</th>
                        @for($h=5;$h<=22;$h++)
                        <th class="text-muted" style="font-size:.65rem;width:30px">{{ sprintf('%02d:00',$h) }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($shifts as $s)
                    <tr>
                        <td class="text-start fw-semibold" style="min-width:120px">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $s->warna ?? '#8b5cf6' }};display:inline-block;margin-right:6px"></span>
                            {{ $s->nama }}
                        </td>
                        @php $startH = (int)substr($s->jam_masuk,0,2); $endH = (int)substr($s->jam_pulang,0,2); @endphp
                        @for($h=5;$h<=22;$h++)
                        <td class="p-1" style="background:{{ $h>=$startH && $h<$endH ? ($s->warna ?? '#8b5cf6').'33' : 'transparent' }}">
                            @if($h>=$startH && $h<$endH)<span style="width:8px;height:8px;border-radius:50%;background:{{ $s->warna ?? '#8b5cf6' }};display:inline-block"></span>@endif
                        </td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection