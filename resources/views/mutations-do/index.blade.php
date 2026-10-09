@extends('layouts.master')
@section('title') Drop Out @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Peserta Didik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Drop Out @endslot
    @endcomponent

    {{-- STATISTIK (khusus data drop out, mengikuti pencarian aktif) --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($statistics['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh data drop out</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-draft-line text-secondary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Draft</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($statistics['draft'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Belum diajukan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-send-plane-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Diajukan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($statistics['submitted'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Menunggu verifikasi</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Disetujui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($statistics['approved'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Sudah diproses</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-close-circle-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Ditolak</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($statistics['rejected'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tidak diproses</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Drop Out</h5>
                            <p class="text-muted mb-0">
                                Daftar santri yang drop out.
                                <span class="badge bg-primary-subtle text-primary ms-1">{{ number_format($statistics['total'] ?? 0) }} data</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.mutations-do.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Drop Out
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="filter-badge {{ !request('status') ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'draft']) }}" class="filter-badge {{ request('status') === 'draft' ? 'active' : '' }}">Draft</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'submitted']) }}" class="filter-badge {{ request('status') === 'submitted' ? 'active' : '' }}">Diajukan</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'approved']) }}" class="filter-badge {{ request('status') === 'approved' ? 'active' : '' }}">Disetujui</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected']) }}" class="filter-badge {{ request('status') === 'rejected' ? 'active' : '' }}">Ditolak</a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Nama, NISN..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
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
                            <a href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Santri</th>
                                    <th>NISN</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mutations as $i => $m)
                                    <tr>
                                        <td>{{ $mutations->firstItem() + $i }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $m->student_name }}</span>
                                            @if($m->student)
                                                <br><small class="text-muted">{{ $m->student->school?->name ?? '-' }}</small>
                                            @endif
                                        </td>
                                        <td><code>{{ $m->student_nisn ?: '-' }}</code></td>
                                        <td><small>{{ \Illuminate\Support\Str::limit($m->reason, 60) ?: '-' }}</small></td>
                                        <td>
                                            <span class="badge bg-{{ $m->status_color }}-subtle text-{{ $m->status_color }}">
                                                {{ $m->status_text }}
                                            </span>
                                        </td>
                                        <td><small>{{ $m->created_at->format('d/m/Y') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.mutations-do.show', ['userId' => $userId, 'mutationUuid' => $m->id]) }}">
                                                            <i class="ri-eye-line text-primary me-2"></i>Lihat Detail
                                                        </a>
                                                    </li>
                                                    @if($m->status === 'draft')
                                                        <li>
                                                            <form action="{{ route('user.mutations-do.submit', ['userId' => $userId, 'mutationUuid' => $m->id]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="ri-send-plane-line text-warning me-2"></i>Ajukan
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
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-user-forbid-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data drop out</h5>
                                            <a href="{{ route('user.mutations-do.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                                                <i class="ri-add-line me-1"></i>Ajukan
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($mutations->hasPages())
                        @include('shared._pagination', ['paginator' => $mutations])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
