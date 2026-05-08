@extends('layouts.master')
@section('title') Daftar Asrama @endsection
@section('css')
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('title') Daftar Asrama @endslot
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

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-hotel-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Asrama</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Asrama Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['active']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-location-line text-info"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Asrama Putra</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['putra']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-user-heart-line text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Asrama Putri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['putri']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Asrama</h5>
                            <p class="text-muted mb-0">Pengelolaan asrama pondok pesantren.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Asrama
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        @if(!app('request')->attributes->get('schoolContextId'))
                        <div class="col-md-3">
                            <select name="school_id" class="form-control">
                                <option value="">Semua Sekolah</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <select name="gender" class="form-control">
                                <option value="">Semua Gender</option>
                                <option value="putra" {{ request('gender') == 'putra' ? 'selected' : '' }}>Putra</option>
                                <option value="putri" {{ request('gender') == 'putri' ? 'selected' : '' }}>Putri</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / kode asrama..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line"></i> Cari</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Kode</th>
                                    <th>Nama Asrama</th>
                                    <th>Sekolah</th>
                                    <th>Gender</th>
                                    <th class="text-center">Kapasitas</th>
                                    <th class="text-center">Penghuni</th>
                                    <th class="text-center">Kamar</th>
                                    <th class="text-center">Gedung</th>
                                    <th>Kepala Asrama</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dormitories as $i => $d)
                                <tr>
                                    <td class="text-center">{{ $i + 1 + ($dormitories->currentPage() - 1) * $dormitories->perPage() }}</td>
                                    <td><span class="badge bg-dark">{{ $d->code }}</span></td>
                                    <td>
                                        <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $d->id]) }}" class="text-decoration-none fw-semibold">
                                            {{ $d->name }}
                                        </a>
                                    </td>
                                    <td>{{ $d->school?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $d->gender === 'putra' ? 'primary' : 'danger' }}">
                                            {{ $d->gender === 'putra' ? 'Putra' : 'Putri' }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ number_format($d->capacity) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $d->total_residents ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">{{ number_format($d->rooms?->count() ?? 0) }}</td>
                                    <td class="text-center">{{ number_format($d->wings?->count() ?? 0) }}</td>
                                    <td>{{ $d->head?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $d->is_active ? 'success' : 'secondary' }}">
                                            {{ $d->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="ri-more-line"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $d->id]) }}">
                                                    <i class="ri-eye-line me-1"></i> Detail
                                                </a></li>
                                                <li><a class="dropdown-item" href="{{ route('user.asrama.edit', ['userId' => $userId, 'asramaUuid' => $d->id]) }}">
                                                    <i class="ri-edit-line me-1"></i> Edit
                                                </a></li>
                                                <li>
                                                    <form action="{{ route('user.asrama.destroy', ['userId' => $userId, 'asramaUuid' => $d->id]) }}" method="POST" onsubmit="return confirm('Yakin hapus asrama ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="ri-delete-bin-line me-1"></i> Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">
                                        <i class="ri-hotel-line fs-1 d-block mb-2"></i>
                                        Belum ada data asrama.
                                        <a href="{{ route('user.asrama.create', ['userId' => $userId]) }}">Tambah asrama baru</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $dormitories->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
