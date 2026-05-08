@extends('layouts.master')
@section('title') PD Masuk @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') PD Masuk @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">PD Masuk</h5>
                            <p class="text-muted mb-0">Daftar surat rekomendasi mutasi masuk.</p>
                        </div>
                        <a href="{{ route('user.mutations-in.create', ['userId' => $userId]) }}" class="btn btn-success">
                            <i class="ri-add-line align-bottom me-1"></i> Ajukan PD Masuk
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Nama, No. Surat..." value="{{ request('search') }}">
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
                            <a href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Santri</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Sekolah Asal</th>
                                    <th>Kelas Diterima</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mutations as $i => $m)
                                    <tr>
                                        <td>{{ $mutations->firstItem() + $i }}</td>
                                        <td><span class="fw-semibold">{{ $m->student_name }}</span></td>
                                        <td>
                                            <span class="badge bg-{{ $m->student_gender === 'P' ? 'danger' : 'primary' }}-subtle text-{{ $m->student_gender === 'P' ? 'danger' : 'primary' }}">
                                                {{ $m->gender_text }}
                                            </span>
                                        </td>
                                        <td><small>{{ $m->student_previous_school ?: '-' }}</small></td>
                                        <td><small>{{ $m->accepted_class ? 'Kelas ' . $m->accepted_class : '-' }}</small></td>
                                        <td>
                                            <span class="badge bg-{{ $m->status_color }}-subtle text-{{ $m->status_color }}">
                                                {{ $m->status_text }}
                                            </span>
                                        </td>
                                        <td><small>{{ $m->created_at->format('d/m/Y') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.mutations-in.show', ['userId' => $userId, 'mutationUuid' => $m->id]) }}">
                                                            <i class="ri-eye-line text-primary me-2"></i>Lihat Detail
                                                        </a>
                                                    </li>
                                                    @if($m->status === 'approved')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('user.mutations-in.print', ['userId' => $userId, 'mutationUuid' => $m->id]) }}" target="_blank">
                                                                <i class="ri-printer-line text-info me-2"></i>Cetak
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($m->status === 'draft')
                                                        <li>
                                                            <form action="{{ route('user.mutations-in.submit', ['userId' => $userId, 'mutationUuid' => $m->id]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item"><i class="ri-send-plane-line text-warning me-2"></i>Ajukan</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="ri-login-box-line fs-1 opacity-50"></i>
                                            <h5 class="mt-2 text-muted">Belum ada data PD Masuk</h5>
                                            <a href="{{ route('user.mutations-in.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm mt-2">
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
