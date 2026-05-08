@extends('layouts.master')
@section('title') Lulus @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Lulus @endslot
    @endcomponent

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
                            <h5 class="card-title mb-0">Data Santri Lulus</h5>
                            <p class="text-muted mb-0">Daftar santri yang telah lulus.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.mutations-lulus.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Lulus
                            </a>
                        </div>
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
                            <a href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Santri</th>
                                    <th>NISN</th>
                                    <th>Tahun Lulus</th>
                                    <th>No. Ijazah</th>
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
                                        <td>{{ $m->graduation_year ?: '-' }}</td>
                                        <td><code>{{ $m->graduation_certificate_number ?: '-' }}</code></td>
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
                                                        <a class="dropdown-item" href="{{ route('user.mutations-lulus.show', ['userId' => $userId, 'mutationUuid' => $m->id]) }}">
                                                            <i class="ri-eye-line text-primary me-2"></i>Lihat Detail
                                                        </a>
                                                    </li>
                                                    @if($m->status === 'approved')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('user.mutations-lulus.print', ['userId' => $userId, 'mutationUuid' => $m->id]) }}" target="_blank">
                                                                <i class="ri-printer-line text-info me-2"></i>Cetak
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($m->status === 'draft')
                                                        <li>
                                                            <form action="{{ route('user.mutations-lulus.submit', ['userId' => $userId, 'mutationUuid' => $m->id]) }}" method="POST" class="d-inline">
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
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light rounded-circle">
                                                    <i class="ri-graduation-cap-line fs-1 text-muted"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-muted">Belum ada data lulusan</h5>
                                            <a href="{{ route('user.mutations-lulus.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
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
