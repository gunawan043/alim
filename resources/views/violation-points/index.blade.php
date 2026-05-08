@extends('layouts.master')
@section('title') Poin Pelanggaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK & Peserta Didik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('title') Poin Pelanggaran @endslot
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
                            <h5 class="card-title mb-0">Poin Pelanggaran</h5>
                            <p class="text-muted mb-0">Daftar pencatatan poin pelanggaran peserta didik.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.violation-points.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Pelanggaran
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Nama / Jenis Pelanggaran..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="study_group_id" class="form-control">
                                <option value="">Semua Rombel</option>
                                @foreach($studyGroups as $sg)
                                    <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="Dari Tanggal">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="Sampai Tanggal">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Peserta Didik</th>
                                    <th>Rombel</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th class="text-center">Poin</th>
                                    <th>Tindakan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($violations as $i => $v)
                                    <tr>
                                        <td>{{ $violations->firstItem() + $i }}</td>
                                        <td>{{ $v->violation_date->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $v->student?->name ?? '-' }}</span>
                                            @if($v->student?->nisn)
                                                <br><small class="text-muted">NISN: {{ $v->student->nisn }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $v->studyGroup?->full_name ?? '-' }}</td>
                                        <td>{{ $v->violation_type }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">{{ $v->points }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ Str::limit($v->action_taken, 40) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.violation-points.show', ['userId' => $userId, 'violationUuid' => $v->id]) }}"
                                               class="btn btn-sm btn-outline-primary me-1" title="Detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.violation-points.edit', ['userId' => $userId, 'violationUuid' => $v->id]) }}"
                                               class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('user.violation-points.destroy', ['userId' => $userId, 'violationUuid' => $v->id]) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Hapus">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                                            Belum ada data pelanggaran.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $violations->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
