@extends('layouts.master')
@section('title') Daftar Asrama @endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.card-animate { transition: all 0.3s ease; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.school-card { cursor: pointer; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pengelolaan @endslot
        @slot('title') Daftar Asrama @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row g-3 mb-2">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100 border-start border-primary border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary rounded fs-2"><i class="ri-hotel-line text-white"></i></span>
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
            <div class="card card-animate h-100 border-start border-success border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success rounded fs-2"><i class="ri-checkbox-circle-line text-white"></i></span>
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
            <div class="card card-animate h-100 border-start border-info border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info rounded fs-2"><i class="ri-user-location-line text-white"></i></span>
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
            <div class="card card-animate h-100 border-start border-warning border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning rounded fs-2"><i class="ri-user-heart-line text-white"></i></span>
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
                            <h5 class="card-title mb-0">Daftar Asrama</h5>
                            <p class="text-muted mb-0">Pengelolaan asrama pondok pesantren.</p>
                        </div>
                        <div class="col-sm-auto">
                            @if(auth()->user()->hasPermissionTo('dormitory-master-create'))
                            <a href="{{ route('user.dormitory-master.create', ['userId' => $userId]) }}" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah Asrama
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="work_unit_id" class="form-control">
                                <option value="">Semua Unit Pengasuhan</option>
                                @foreach($workUnits as $wu)
                                    <option value="{{ $wu->id }}" {{ request('work_unit_id') == $wu->id ? 'selected' : '' }}>{{ str_replace('Pengasuhan', 'Asrama', $wu->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="gender" class="form-control">
                                <option value="">Semua Gender</option>
                                <option value="putra" {{ request('gender') == 'putra' ? 'selected' : '' }}>Putra</option>
                                <option value="putri" {{ request('gender') == 'putri' ? 'selected' : '' }}>Putri</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-control">
                                <option value="">Semua</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama / kode asrama..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Cari</button>
                        </div>
                    </form>

                    {{-- Card Grid --}}
                    <div class="row g-4">
                        @forelse($dormitories as $d)
                            <div class="col-xxl-3 col-lg-4 col-md-6">
                                <div class="card border card-shadow h-100 card-animate">
                                    <div class="card-header bg-{{ $d->gender === 'putra' ? 'primary' : 'danger' }}-subtle py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-shrink-0">
                                                @if($d->logo_path && file_exists(public_path($d->logo_path)))
                                                    <img src="{{ asset($d->logo_path) }}" alt="{{ $d->name }}" class="rounded-circle" width="52" height="52" style="object-fit:cover;border:2px solid white">
                                                @else
                                                    <div class="avatar-xl bg-{{ $d->gender === 'putra' ? 'primary' : 'danger' }}-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:52px;height:52px;border:2px solid white">
                                                        <span class="fs-4 fw-bold text-{{ $d->gender === 'putra' ? 'primary' : 'danger' }}">{{ strtoupper(substr($d->name,0,1)) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <h6 class="mb-1 text-truncate" title="{{ $d->name }}">{{ $d->name }}</h6>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <span class="badge bg-{{ $d->gender === 'putra' ? 'primary' : 'danger' }}-subtle text-{{ $d->gender === 'putra' ? 'primary' : 'danger' }}">
                                                        {{ $d->gender === 'putra' ? 'Putra' : 'Putri' }}
                                                    </span>
                                                    @if(!$d->is_active)
                                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ri-shield-star-line text-muted"></i>
                                            <small class="text-muted">Kode: <strong>{{ $d->code ?? '-' }}</strong></small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ri-hotel-line text-muted"></i>
                                            <small class="text-muted">Unit: <strong>{{ str_replace('Pengasuhan', 'Asrama', $d->workUnit?->name) ?? '-' }}</strong></small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ri-user-star-line text-muted"></i>
                                            <small class="text-muted">{{ $d->head?->name ?? 'Belum ada kepala asrama' }}</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-group-line text-muted"></i>
                                            <small class="text-muted">Penghuni: <strong>{{ $d->total_residents ?? 0 }}</strong> / {{ $d->capacity ?? 0 }}</small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-dashed">
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('user.dormitory-master.show', ['userId' => $userId, 'asramaUuid' => $d->id]) }}" class="btn btn-sm btn-soft-primary flex-grow-1">
                                                <i class="ri-eye-line me-1"></i> Detail
                                            </a>
                                            @if(auth()->user()->hasPermissionTo('dormitory-master-update'))
                                            <a href="{{ route('user.dormitory-master.edit', ['userId' => $userId, 'asramaUuid' => $d->id]) }}" class="btn btn-sm btn-soft-secondary" aria-label="Edit" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <button class="btn btn-sm btn-soft-danger delete-asrama"
                                                data-id="{{ $d->id }}"
                                                data-name="{{ $d->name }}"
                                                aria-label="Hapus" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h5 class="text-muted mt-3">Belum ada data asrama</h5>
                                    <p class="text-muted mb-3">Tambah asrama pertama Anda.</p>
                                    @if(auth()->user()->hasPermissionTo('dormitory-master-create'))
                                    <a href="{{ route('user.dormitory-master.create', ['userId' => $userId]) }}" class="btn btn-primary">
                                        <i class="ri-add-line me-1"></i>Tambah Asrama
                                    </a>
                                    @endif
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if($dormitories->hasPages())
                        @include('shared._pagination', ['paginator' => $dormitories])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">Hapus Asrama?</h4>
                    <p class="text-muted">Asrama <strong id="deleteAsramaName"></strong> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-asrama').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('deleteAsramaName').textContent = this.dataset.name;
            document.getElementById('deleteForm').action = '/' + '{{ $userId }}' + '/dormitory-master/' + this.dataset.id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });
});
</script>
@endsection
