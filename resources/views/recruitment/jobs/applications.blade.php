@extends('layouts.master')
@section('title') Pelamar — {{ $job->judul }} @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <style>
        .stat-pill { padding: 14px 18px; border-radius: 10px; display: flex; align-items: center; gap: 12px; }
        .stat-pill .icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .app-row { transition: background 0.15s; }
        .app-row:hover { background: var(--bs-light-bg-subtle); }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Rekrutmen @endslot
        @slot('li_2_link') {{ route('user.ats.jobs.index', ['userId' => $userId]) }} @endslot
        @slot('li_2') Lowongan @endslot
        @slot('title') Pelamar — {{ $job->judul }} @endslot
    @endcomponent

    {{-- Job Info --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <span class="badge bg-primary-subtle text-primary">{{ $job->kode_lowongan }}</span>
                    <h5 class="mt-2 mb-1">{{ $job->judul }}</h5>
                    <p class="text-muted mb-0">
                        <i class="ri-briefcase-line me-1"></i>{{ $job->posisi }}
                        <span class="mx-2">•</span>
                        <i class="ri-map-pin-line me-1"></i>{{ $job->lokasi ?? $job->penempatan ?? '-' }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('user.ats.jobs.show', ['userId' => $userId, 'job' => $job->id]) }}" class="btn btn-light btn-sm">
                        <i class="ri-arrow-left-line"></i> Detail Lowongan
                    </a>
                    <a href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="ri-list-check"></i> Semua Lamaran
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="stat-pill bg-primary-subtle">
                <div class="icon bg-primary text-white"><i class="ri-user-line"></i></div>
                <div><div class="text-muted small">Total</div><div class="fw-bold fs-18">{{ $stats['total'] }}</div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-pill bg-warning-subtle">
                <div class="icon bg-warning text-white"><i class="ri-time-line"></i></div>
                <div><div class="text-muted small">Dalam Proses</div><div class="fw-bold fs-18">{{ $stats['proses'] }}</div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-pill bg-success-subtle">
                <div class="icon bg-success text-white"><i class="ri-user-check-line"></i></div>
                <div><div class="text-muted small">Diterima</div><div class="fw-bold fs-18">{{ $stats['diterima'] }}</div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-pill bg-danger-subtle">
                <div class="icon bg-danger text-white"><i class="ri-user-close-line"></i></div>
                <div><div class="text-muted small">Ditolak</div><div class="fw-bold fs-18">{{ $stats['ditolak'] }}</div></div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Cari Nama / Email</label>
                    <input type="text" name="q" class="form-control" placeholder="Ketik nama atau email..." value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach(['menunggu_seleksi', 'seleksi_administrasi', 'lolos_administrasi', 'tes_tertulis', 'lolos_tes', 'wawancara', 'lolos_wawancara', 'diterima', 'ditolak'] as $st)
                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="ri-search-line"></i> Filter</button>
                    <a href="{{ route('user.ats.jobs.applications', ['userId' => $userId, 'job' => $job->id]) }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No Lamaran</th>
                            <th>Nama Pelamar</th>
                            <th>Email</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            <tr class="app-row">
                                <td><span class="fw-medium">#{{ $app->no_lamaran }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $app->recruitmentProfile->user->avatar ? URL::asset('images/' . $app->recruitmentProfile->user->avatar) : URL::asset('build/images/users/avatar-1.jpg') }}"
                                             class="rounded-circle" width="32" height="32" alt="">
                                        <span>{{ $app->recruitmentProfile->user->name }}</span>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $app->recruitmentProfile->user->email }}</td>
                                <td class="text-muted small">{{ $app->tanggal_melamar?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $app->status_color ?? 'secondary' }}-subtle text-{{ $app->status_color ?? 'secondary' }}">
                                        {{ $app->status_label ?? ucwords(str_replace('_',' ', $app->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($app->nilai_akhir)
                                        <span class="badge bg-{{ $app->nilai_akhir >= 75 ? 'success' : ($app->nilai_akhir >= 60 ? 'warning' : 'danger') }}">
                                            {{ number_format($app->nilai_akhir, 1) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('user.ats.applications.show', ['userId' => $userId, 'application' => $app->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada pelamar untuk lowongan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
@endsection
