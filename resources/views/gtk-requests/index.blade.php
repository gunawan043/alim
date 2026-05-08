@extends('layouts.master')
@section('title') Daftar Request GTK @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('title') Daftar Request GTK @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Request GTK</h5>
                            <p class="text-muted mb-0">Pengelolaan pengajuan GTK.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="dropdown">
                                <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="ri-add-line align-bottom me-1"></i> Ajukan Request
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('user.gtk-requests.create', ['userId' => $userId, 'type' => 'procurement']) }}">
                                        <i class="ri-file-add-line text-primary me-2"></i>Pengadaan GTK
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('user.gtk-requests.create', ['userId' => $userId, 'type' => 'trial']) }}">
                                        <i class="ri-user-add-line text-info me-2"></i>Pengangkatan Percobaan
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('user.gtk-requests.create', ['userId' => $userId, 'type' => 'status_increase']) }}">
                                        <i class="ri-arrow-up-line text-warning me-2"></i>Kenaikan Status GTK
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari satuan kerja..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-control">
                                <option value="">Semua Tipe</option>
                                <option value="procurement" {{ request('type') === 'procurement' ? 'selected' : '' }}>Pengadaan GTK</option>
                                <option value="trial" {{ request('type') === 'trial' ? 'selected' : '' }}>Pengangkatan Percobaan</option>
                                <option value="status_increase" {{ request('type') === 'status_increase' ? 'selected' : '' }}>Kenaikan Status GTK</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Tercadangkan</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tipe Request</th>
                                    <th>Satuan Kerja</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Jumlah Item</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $i => $req)
                                    <tr>
                                        <td>{{ $requests->firstItem() + $i }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $req->type_text }}</span>
                                        </td>
                                        <td>{{ $req->workUnit?->name ?? '-' }}</td>
                                        <td>{{ $req->academicYear?->name ?? '-' }}</td>
                                        <td>
                                            @if($req->items->count())
                                                <span class="badge bg-light text-dark">{{ $req->items->count() }} item</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $req->status_color }}-subtle text-{{ $req->status_color }}">
                                                {{ $req->status_text }}
                                            </span>
                                        </td>
                                        <td><small>{{ $req->created_at->format('d/m/Y') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.gtk-requests.show', ['userId' => $userId, 'requestUuid' => $req->id]) }}">
                                                            <i class="ri-eye-line text-primary me-2"></i>Lihat Detail
                                                        </a>
                                                    </li>
                                                    @if($req->status === 'draft')
                                                        <li>
                                                            <form action="{{ route('user.gtk-requests.submit', ['userId' => $userId, 'id' => $req->id]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="ri-send-plane-line text-info me-2"></i>Ajukan
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada request GTK.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requests->hasPages())
                        @include('shared._pagination', ['paginator' => $requests])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
