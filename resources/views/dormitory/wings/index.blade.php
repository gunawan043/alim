@extends('layouts.master')
@section('title') Gedung Asrama — {{ $dormitory->name }} @endsection
@section('css')
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Gedung @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-hotel-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Gedung</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($wings->total()) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-door-open-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Kamar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $wings->total() ? $wings->sum('rooms') : 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-location-line text-info"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Penghuni Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $wings->total() ? $wings->sum('rooms.*.residents') : 0 }}</h3>
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
                            <h5 class="card-title mb-0">Gedung — {{ $dormitory->name }}</h5>
                            <p class="text-muted mb-0">Pengelolaan gedung asrama.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.wings.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Gedung
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / kode gedung..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="is_active" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
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
                                    <th>Nama Gedung</th>
                                    <th class="text-center">Lantai</th>
                                    <th class="text-center">Kapasitas</th>
                                    <th class="text-center">Kamar</th>
                                    <th>Supervisor</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($wings as $w)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration + ($wings->currentPage() - 1) * $wings->perPage() }}</td>
                                    <td><span class="badge bg-dark">{{ $w->code }}</span></td>
                                    <td>
                                        <a href="{{ route('user.asrama.wings.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}" class="text-decoration-none fw-semibold">
                                            {{ $w->name }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $w->floor ?? '—' }}</td>
                                    <td class="text-center">{{ number_format($w->capacity) }}</td>
                                    <td class="text-center">{{ number_format($w->rooms->count()) }}</td>
                                    <td>{{ $w->supervisor?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $w->is_active ? 'success' : 'secondary' }}">
                                            {{ $w->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="ri-more-line"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('user.asrama.wings.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}">
                                                    <i class="ri-eye-line me-1"></i> Detail
                                                </a></li>
                                                <li><a class="dropdown-item" href="{{ route('user.asrama.wings.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}">
                                                    <i class="ri-edit-line me-1"></i> Edit
                                                </a></li>
                                                <li>
                                                    <form action="{{ route('user.asrama.wings.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'wingUuid' => $w->id]) }}" method="POST" onsubmit="return confirm('Yakin hapus gedung ini?')">
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
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ri-door-open-line fs-1 d-block mb-2"></i>
                                        Belum ada data gedung.
                                        <a href="{{ route('user.asrama.wings.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Tambah gedung baru</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">Menampilkan {{ $wings->firstItem() ?? 0 }} - {{ $wings->lastItem() ?? 0 }} dari {{ $wings->total() }} data</div>
                        <div>{{ $wings->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
