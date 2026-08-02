@extends('layouts.master')
@section('title') Kamar Asrama — {{ $dormitory->name }} @endsection
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
        @slot('title') Kamar @endslot
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
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-door-open-line text-primary"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Kamar</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($rooms->total()) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-user-location-line text-success"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $rooms->total() ? $occupied : 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-warning"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kosong</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $rooms->total() ? $totalCapacity - $occupied : 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-heart-line text-info"></i></span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kapasitas</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($rooms->sum('capacity')) }}</h3>
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
                            <h5 class="card-title mb-0">Kamar — {{ $dormitory->name }}</h5>
                            <p class="text-muted mb-0">Pengelolaan kamar asrama.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-outline-primary me-1">
                                <i class="ri-shield-user-line align-bottom me-1"></i> Wali Kamar
                            </a>
                            <a href="{{ route('user.asrama.rooms.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Kamar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="wing_id" class="form-control">
                                <option value="">Semua Gedung</option>
                                @foreach($wings as $w)
                                    <option value="{{ $w->id }}" {{ request('wing_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / kode kamar..." value="{{ request('search') }}">
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
                                    <th>Nama Kamar</th>
                                    <th>Sayap</th>
                                    <th>Wali Kamar</th>
                                    <th class="text-center">Lantai</th>
                                    <th class="text-center">Tipe</th>
                                    <th class="text-center">Kapasitas</th>
                                    <th class="text-center">Penghuni</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rooms as $r)
                                @php $activeCount = $r->residents->filter(fn($res) => $res->is_active)->count(); @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration + ($rooms->currentPage() - 1) * $rooms->perPage() }}</td>
                                    <td><span class="badge bg-dark rounded-pill">{{ $r->code }}</span></td>
                                    <td>
                                        <a href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}" class="text-decoration-none fw-semibold">
                                            {{ $r->name ?? $r->code }}
                                        </a>
                                    </td>
                                    <td>{{ $r->wing?->name ?? '—' }}</td>
                                    <td>
                                        @if($r->activeSupervisor?->user)
                                            <span class="fw-semibold">{{ $r->activeSupervisor->user->name }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $r->floor ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($r->room_type)
                                            <span class="badge bg-{{ $r->room_type === 'musyrif' ? 'warning' : 'info' }}-subtle rounded-pill">
                                                {{ ucfirst($r->room_type) }}
                                            </span>
                                        @else — @endif
                                    </td>
                                    <td class="text-center">{{ number_format($r->capacity) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $activeCount >= $r->capacity ? 'danger' : 'success' }} rounded-pill">
                                            {{ $activeCount }}/{{ $r->capacity }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $r->is_active ? 'success' : 'secondary' }} rounded-pill">
                                            {{ $r->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-label="Opsi lainnya" title="Opsi lainnya">
                                                <i class="ri-more-line"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('user.asrama.rooms.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}">
                                                    <i class="ri-eye-line me-1"></i> Detail
                                                </a></li>
                                                <li><a class="dropdown-item" href="{{ route('user.asrama.rooms.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}">
                                                    <i class="ri-edit-line me-1"></i> Edit
                                                </a></li>
                                                <li>
                                                    <form action="{{ route('user.asrama.rooms.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'roomUuid' => $r->id]) }}" method="POST" onsubmit="return confirm('Yakin hapus kamar ini?')">
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
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="ri-door-open-line fs-1 d-block mb-2"></i>
                                        Belum ada data kamar.
                                        <a href="{{ route('user.asrama.rooms.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Tambah kamar baru</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">Menampilkan {{ $rooms->firstItem() ?? 0 }} - {{ $rooms->lastItem() ?? 0 }} dari {{ $rooms->total() }} data</div>
                        <div>{{ $rooms->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
